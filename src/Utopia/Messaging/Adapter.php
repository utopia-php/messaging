<?php

namespace Utopia\Messaging;

abstract class Adapter
{
    /**
     * Get the name of the adapter.
     */
    abstract public function getName(): string;

    /**
     * Get the type of the adapter.
     */
    abstract public function getType(): string;

    /**
     * Get the type of the message the adapter can send.
     */
    abstract public function getMessageType(): string;

    /**
     * Get the maximum number of messages that can be sent in a single request.
     */
    abstract public function getMaxMessagesPerRequest(): int;

    /**
     * Send a message.
     *
     * @param  Message  $message The message to send.
     * @return array The results array.
     *
     * @throws \Exception If the message fails.
     */
    public function send(Message $message): array
    {
        if (! \is_a($message, $this->getMessageType())) {
            throw new \Exception('Invalid message type.');
        }
        if (\method_exists($message, 'getTo') && \count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        if (! \method_exists($this, 'process')) {
            throw new \Exception('Adapter does not implement process method.');
        }

        return $this->process($message);
    }

    /**
     * Send an HTTP request.
     *
     * @param  string  $method The HTTP method to use.
     * @param  string  $url The URL to send the request to.
     * @param  array<string>  $headers An array of headers to send with the request.
     * @param  string|null  $body The body of the request.
     * @return array<string, mixed> The response body.
     *
     * @throws \Exception If the request fails.
     */
    protected function request(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null,
    ): array {
        $ch = \curl_init();

        if (! \is_null($body)) {
            $headers[] = 'Content-Length: '.\strlen($body);
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        \curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => "Appwrite {$this->getName()} Message Sender",
        ]);

        $response = \curl_exec($ch);

        \curl_close($ch);

        try {
            $response = \json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } finally {
            return [
                'url' => $url,
                'statusCode' => \curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
                'response' => $response,
                'error' => \curl_error($ch),
            ];
        }
    }

    protected function requestMulti(
        string $method,
        array $urls,
        array $headers = [],
        array $bodies = [],
    ): array {
        $sh = \curl_share_init();
        $mh = \curl_multi_init();
        $ch = \curl_init();

        \curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
        \curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);

        $headers[] = 'Content-Length: '.\strlen($bodies[0]);

        \curl_setopt_array($ch, [
            CURLOPT_SHARE => $sh,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_PORT => 443,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $bodies[0],
            CURLOPT_URL => $urls[0],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FORBID_REUSE => false,
            CURLOPT_FRESH_CONNECT => false,
        ]);

        /**
         * Create a handle for each request.
         * If there are more urls than bodies, use the first body for all requests.
         * If there are more bodies than urls, use the first url for all requests.
         */
        if (\count($urls) >= \count($bodies)) {
            foreach ($urls as $url) {
                \curl_setopt($ch, CURLOPT_URL, $url);
                \curl_multi_add_handle($mh, \curl_copy_handle($ch));
            }
        }
        if (\count($urls) <= \count($bodies)) {
            foreach ($bodies as $body) {
                $headers[] = 'Content-Length: '.\strlen($body);

                \curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                \curl_multi_add_handle($mh, \curl_copy_handle($ch));
            }
        }

        $active = true;
        do {
            $status = \curl_multi_exec($mh, $active);

            if ($active) {
                \curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        $responses = [];

        // Check each handle's result
        while ($info = \curl_multi_info_read($mh)) {
            $ch = $info['handle'];

            $response = \curl_multi_getcontent($ch);

            try {
                $response = \json_decode($response, true, flags: JSON_THROW_ON_ERROR);
            } finally {
                $responses[] = [
                    'url' => \curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
                    'statusCode' => \curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
                    'response' => $response,
                    'error' => \curl_error($ch),
                ];
            }

            \curl_multi_remove_handle($mh, $ch);
            \curl_close($ch);
        }

        \curl_multi_close($mh);
        \curl_share_close($sh);

        return $responses;
    }
}
