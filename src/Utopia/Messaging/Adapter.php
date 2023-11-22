<?php

namespace Utopia\Messaging;

abstract class Adapter
{
    /**
     * Get the name of the adapter.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get the type of the adapter.
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * Get the type of the message the adapter can send.
     *
     * @return string
     */
    abstract public function getMessageType(): string;

    /**
     * Get the maximum number of messages that can be sent in a single request.
     *
     * @return int
     */
    abstract public function getMaxMessagesPerRequest(): int;

    /**
     * Send a message.
     *
     * @param  Message  $message The message to send.
     * @return string The response body.
     */
    abstract public function send(Message $message): string;

    /**
     * Send an HTTP request.
     *
     * @param  string  $method The HTTP method to use.
     * @param  string  $url The URL to send the request to.
     * @param  array  $headers An array of headers to send with the request.
     * @param  string|null  $body The body of the request.
     * @return string The response body.
     *
     * @throws \Exception If the request fails.
     */
    protected function request(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null,
    ): string {
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

        $response = \curl_exec($ch);

        \curl_close($ch);

        return \json_encode([
            'response' => \json_decode($response, true),
            'statusCode' => \curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ]);
    }
}
