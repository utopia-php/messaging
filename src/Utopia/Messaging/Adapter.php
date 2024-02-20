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
     * @return array{
     *     deliveredTo: int,
     *     type: string,
     *     results: array<array<string, mixed>>
     * } | array<string, array{
     *     deliveredTo: int,
     *     type: string,
     *     results: array<array<string, mixed>>
     * }> GEOSMS adapter returns an array of results keyed by adapter name.
     *
     * @throws \Exception
     */
    public function send(Message $message): array
    {
        if (!\is_a($message, $this->getMessageType())) {
            throw new \Exception('Invalid message type.');
        }
        if (\method_exists($message, 'getTo') && \count($message->getTo()) > $this->getMaxMessagesPerRequest()) {
            throw new \Exception("{$this->getName()} can only send {$this->getMaxMessagesPerRequest()} messages per request.");
        }
        if (!\method_exists($this, 'process')) {
            throw new \Exception('Adapter does not implement process method.');
        }

        return $this->process($message);
    }

    /**
     * Send a single HTTP request.
     *
     * @param  string  $method The HTTP method to use.
     * @param  string  $url The URL to send the request to.
     * @param  array<string>  $headers An array of headers to send with the request.
     * @param  array<string, mixed>|null  $body The body of the request.
     * @param  int  $timeout The timeout in seconds.
     * @return array{
     *     url: string,
     *     statusCode: int,
     *     response: array<string, mixed>|string|null,
     *     error: string|null
     * }
     *
     * @throws \Exception If the request fails.
     */
    protected function request(
        string $method,
        string $url,
        array $headers = [],
        array $body = null,
        int $timeout = 30
    ): array {
        $ch = \curl_init();

        foreach ($headers as $header) {
            if (\str_contains('application/json', $header)) {
                $body = \json_encode($body);
                break;
            }
            if (\str_contains('application/x-www-form-urlencoded', $header)) {
                $body = \http_build_query($body);
                break;
            }
        }

        if (!\is_null($body)) {
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            if (\is_string($body)) {
                $headers[] = 'Content-Length: '.\strlen($body);
            }
        }

        \curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "Appwrite {$this->getName()} Message Sender",
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $response = \curl_exec($ch);

        \curl_close($ch);

        try {
            $response = \json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Ignore
        }

        return [
            'url' => $url,
            'statusCode' => \curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'response' => $response,
            'error' => \curl_error($ch),
        ];
    }

    /**
     * Send multiple concurrent HTTP requests using HTTP/2 multiplexing.
     *
     * @param  array<string>  $urls
     * @param  array<string>  $headers
     * @param  array<array<string, mixed>>  $bodies
     * @return array<array{
     *     url: string,
     *     statusCode: int,
     *     response: array<string, mixed>|null,
     *     error: string|null
     * }>
     *
     * @throws \Exception
     */
    protected function requestMulti(
        string $method,
        array $urls,
        array $headers = [],
        array $bodies = [],
        int $timeout = 30
    ): array {
        if (empty($urls)) {
            throw new \Exception('No URLs provided. Must provide at least one URL.');
        }

        foreach ($headers as $header) {
            if (\str_contains('application/json', $header)) {
                foreach ($bodies as $i => $body) {
                    $bodies[$i] = \json_encode($body);
                }
                break;
            }
            if (\str_contains('application/x-www-form-urlencoded', $header)) {
                foreach ($bodies as $i => $body) {
                    $bodies[$i] = \http_build_query($body);
                }
                break;
            }
        }

        $sh = \curl_share_init();
        $mh = \curl_multi_init();
        $ch = \curl_init();

        \curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
        \curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);

        \curl_setopt_array($ch, [
            CURLOPT_SHARE => $sh,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FORBID_REUSE => false,
            CURLOPT_FRESH_CONNECT => false,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $urlCount = \count($urls);
        $bodyCount = \count($bodies);

        if (!($urlCount == $bodyCount || $urlCount == 1 || $bodyCount == 1)) {
            throw new \Exception('URL and body counts must be equal or one must equal 1.');
        }

        if ($urlCount > $bodyCount) {
            $bodies = \array_pad($bodies, $urlCount, $bodies[0]);
        } elseif ($urlCount < $bodyCount) {
            $urls = \array_pad($urls, $bodyCount, $urls[0]);
        }

        for ($i = 0; $i < \count($urls); $i++) {
            if (!empty($bodies[$i])) {
                $headers[] = 'Content-Length: '.\strlen($bodies[$i]);
            }

            \curl_setopt($ch, CURLOPT_URL, $urls[$i]);
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $bodies[$i]);
            \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            \curl_multi_add_handle($mh, \curl_copy_handle($ch));
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
            } catch (\JsonException) {
                // Ignore
            }

            $responses[] = [
                'url' => \curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
                'statusCode' => \curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
                'response' => $response,
                'error' => \curl_error($ch),
            ];

            \curl_multi_remove_handle($mh, $ch);
            \curl_close($ch);
        }

        \curl_multi_close($mh);
        \curl_share_close($sh);

        return $responses;
    }
}
