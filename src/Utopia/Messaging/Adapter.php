<?php

namespace Utopia\Messaging;

use Exception;
use libphonenumber\PhoneNumberUtil;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Utopia\Client;
use Utopia\Client\Adapter\Curl\Client as CurlAdapter;
use Utopia\Psr7\Header;
use Utopia\Psr7\Request\Factory as RequestFactory;
use Utopia\Telemetry\Adapter as Telemetry;
use Utopia\Telemetry\Adapter\None as NoTelemetry;
use Utopia\Telemetry\Counter;

abstract class Adapter
{
    private Counter $sendCounter;

    public function __construct(?Telemetry $telemetry = null)
    {
        $this->sendCounter = ($telemetry ?? new NoTelemetry())->createCounter('messaging.send');
    }

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

        try {
            $response = $this->process($message);
        } catch (\Throwable $error) {
            $this->recordSend($message, \method_exists($message, 'getTo') ? \count($message->getTo()) : 1, 0);
            throw $error;
        }

        $this->recordResponse($message, $response);

        return $response;
    }

    public function setTelemetry(Telemetry $telemetry): void
    {
        $this->sendCounter = $telemetry->createCounter('messaging.send');
    }

    private function recordSend(Message $message, int $recipients, int $delivered): void
    {
        if ($delivered > 0) {
            $this->sendCounter->add($delivered, $this->telemetryAttributes($message, [
                'result' => 'success',
            ]));
        }

        $failed = $recipients - $delivered;
        if ($failed > 0) {
            $this->sendCounter->add($failed, $this->telemetryAttributes($message, [
                'result' => 'failure',
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function telemetryAttributes(Message $message, array $attributes = []): array
    {
        if ($message->getOrigin() !== null) {
            $attributes['origin'] = $message->getOrigin();
        }

        return $attributes + [
            'type' => $this->getType(),
            'provider' => \strtolower($this->getName()),
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function recordResponse(Message $message, array $response): void
    {
        $results = $response['results'] ?? [];
        if (empty($results)) {
            return;
        }

        $delivered = 0;
        $failed = 0;

        foreach ($results as $result) {
            ($result['status'] ?? '') === 'success' ? $delivered++ : $failed++;
        }

        $this->recordSend($message, $delivered + $failed, $delivered);
    }

    /**
     * Send a single HTTP request and return the client's PSR-7 response.
     *
     * @param  string  $method The HTTP method to use.
     * @param  string  $url The URL to send the request to.
     * @param  array<string>  $headers Headers as "Key: value" strings.
     * @param  array<string, mixed>|null  $body The body of the request.
     * @param  int  $timeout The timeout in seconds.
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If the request fails at the transport level.
     */
    protected function request(
        string $method,
        string $url,
        array $headers = [],
        ?array $body = null,
        int $timeout = 30,
        int $connectTimeout = 10
    ): ResponseInterface {
        return $this->client($timeout, $connectTimeout)
            ->sendRequest($this->buildRequest($method, $url, $headers, $body));
    }

    /**
     * Send multiple HTTP requests over a single kept-alive HTTP/2 connection.
     * Responses are returned in request order, so the Nth response corresponds
     * to the Nth recipient.
     *
     * @param  array<string>  $urls
     * @param  array<string>  $headers Headers as "Key: value" strings.
     * @param  array<array<string, mixed>>  $bodies
     * @return array<ResponseInterface>
     *
     * @throws Exception
     */
    protected function requestMulti(
        string $method,
        array $urls,
        array $headers = [],
        array $bodies = [],
        int $timeout = 30,
        int $connectTimeout = 10
    ): array {
        if (empty($urls)) {
            throw new \Exception('No URLs provided. Must provide at least one URL.');
        }

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

        $client = $this->client($timeout, $connectTimeout, multi: true);

        $responses = [];
        foreach ($urls as $i => $url) {
            $responses[] = $client->sendRequest($this->buildRequest($method, $url, $headers, $bodies[$i]));
        }

        return $responses;
    }

    /**
     * Build a client carrying the adapter's user agent and timeouts. When
     * $multi is set the cURL transport negotiates HTTP/2 and keeps the
     * connection alive so a batch of requests to the same host reuses it.
     */
    private function client(int $timeout, int $connectTimeout, bool $multi = false): Client
    {
        $adapter = new CurlAdapter(
            options: $multi ? [\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_2_0] : [],
        );

        return (new Client($adapter))
            ->withTimeout((float) $timeout)
            ->withConnectTimeout((float) $connectTimeout)
            ->withConnectionReuse($multi)
            ->withHeaders([Header::USER_AGENT => "Appwrite {$this->getName()} Message Sender"]);
    }

    /**
     * Translate the legacy "Key: value" header list and body array into a
     * PSR-7 request, picking the body encoding from the Content-Type header.
     *
     * @param  array<string>  $headers
     * @param  array<string, mixed>|null  $body
     */
    private function buildRequest(string $method, string $url, array $headers, ?array $body): RequestInterface
    {
        $factory = new RequestFactory();
        $contentType = '';
        $map = [];

        foreach ($headers as $header) {
            [$key, $value] = \array_pad(\explode(':', $header, 2), 2, '');
            $key = \trim($key);
            $value = \trim($value);

            if (\strtolower($key) === 'content-type') {
                $contentType = \strtolower($value);

                continue;
            }

            $map[$key] = $value;
        }

        $body ??= [];

        return match (true) {
            \str_contains($contentType, 'application/x-www-form-urlencoded') => $factory->form($method, $url, $body, $map),
            \str_contains($contentType, 'multipart/form-data') => $factory->multipart($method, $url, $body, $map),
            default => $factory->json($method, $url, $body, $map),
        };
    }


    /**
     * @param string $phone
     * @return int|null
     * @throws Exception
     */
    public function getCountryCode(string $phone): ?int
    {
        if (empty($phone)) {
            throw new Exception('$phone cannot be empty.');
        }

        $helper = PhoneNumberUtil::getInstance();

        try {
            return $helper
                ->parse($phone)
                ->getCountryCode();

        } catch (\Throwable $th) {
            throw new Exception("Error parsing phone: " . $th->getMessage());
        }
    }
}
