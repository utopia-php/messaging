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
     * Get the maximum number of messages that can be sent in a single request.
     *
     * @return int
     */
    abstract public function getMaxMessagesPerRequest(): int;

    /**
     * Send a message.
     *
     * @param Message $message The message to send.
     * @return string The response body.
     */
    abstract public function send(Message $message): string;

    /**
     * Send an HTTP request.
     *
     * @param string $method The HTTP method to use.
     * @param string $url The URL to send the request to.
     * @param array $headers An array of headers to send with the request.
     * @param string|null $body The body of the request.
     * @return string The response body.
     * @throws \Exception If the request fails.
     */
    protected function request(
        string $method,
        string $url,
        array $headers = [],
        mixed $body = null,
    ): string {
        $headers[] = 'Content-length: ' . \strlen($body);

        $ch = \curl_init();

        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($ch, CURLOPT_USERAGENT, "Appwrite {$this->getName()} Message Sender");

        if (!is_null($body)) {
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = \curl_exec($ch);

        if (\curl_errno($ch)) {
            throw new \Exception('Error:' . \curl_error($ch));
        }
        if (\curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
            throw new \Exception($response);
        }

        \curl_close($ch);

        return $response;
    }
}
