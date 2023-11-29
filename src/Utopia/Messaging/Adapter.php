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
     * @return string The response body.
     */
    public function send(Message $message): string
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
     */
    protected function request(
        string $method,
        string $url,
        array $headers = [],
        string $body = null,
    ): array {
        $ch = \curl_init();

        if (! \is_null($body)) {
            $headers[] = 'Content-Length: '.\strlen($body);
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($ch, CURLOPT_USERAGENT, "Appwrite {$this->getName()} Message Sender");

        $errno = \curl_errno($ch);
        $response = \curl_exec($ch);

        \curl_close($ch);

        $jsonResponse = \json_decode($response, true);

        if (\json_last_error() == JSON_ERROR_NONE) {
            return [
                'response' => $jsonResponse,
                'statusCode' => \curl_getinfo($ch, CURLINFO_HTTP_CODE),
                'errno' => $errno,
            ];
        }

        return [
            'response' => $response,
            'statusCode' => \curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'errno' => $errno,
        ];
    }
}
