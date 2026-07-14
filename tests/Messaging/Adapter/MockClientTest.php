<?php

namespace Utopia\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Utopia\Messaging\Adapter;
use Utopia\Messaging\Adapter\SMS\Mock;
use Utopia\Messaging\Messages\SMS;
use Utopia\Psr7\Response;
use Utopia\Psr7\Stream;

class MockClient implements ClientInterface
{
    /**
     * @var array<RequestInterface>
     */
    public array $requests = [];

    /**
     * @var array<ResponseInterface|ClientExceptionInterface>
     */
    public array $queue = [];

    public function queueResponse(int $statusCode, string $body = ''): void
    {
        $this->queue[] = new Response($statusCode, body: new Stream($body));
    }

    public function queueException(ClientExceptionInterface $exception): void
    {
        $this->queue[] = $exception;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $next = \array_shift($this->queue) ?? new Response(200);

        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }
}

class MockNetworkException extends \Exception implements NetworkExceptionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        string $message,
        int $code = 0,
    ) {
        parent::__construct($message, $code);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}

class MultiAdapter extends Adapter
{
    public function getName(): string
    {
        return 'Multi';
    }

    public function getType(): string
    {
        return 'sms';
    }

    public function getMessageType(): string
    {
        return SMS::class;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 100;
    }

    /**
     * @param  array<array<string, mixed>>  $bodies
     * @return array<array<string, mixed>>
     */
    public function sendBatch(array $bodies): array
    {
        return $this->requestMulti(
            method: 'POST',
            urls: ['https://example.test/batch'],
            headers: ['Content-Type: application/json'],
            bodies: $bodies,
        );
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function sendOne(array $body): array
    {
        return $this->request(
            method: 'POST',
            url: 'https://example.test/single',
            headers: ['Content-Type: application/json'],
            body: $body,
        );
    }
}

class MockClientTest extends TestCase
{
    public function testSendUsesInjectedClient(): void
    {
        $client = new MockClient();
        $client->queueResponse(200, '{"ok":true}');

        $adapter = new Mock('username', 'password');
        $adapter->setClient($client);

        $result = $adapter->send(new SMS(
            to: ['+123456789'],
            content: 'Test Content',
            from: '+987654321',
        ));

        $this->assertEquals(1, $result['deliveredTo']);
        $this->assertEquals('success', $result['results'][0]['status']);

        $this->assertCount(1, $client->requests);
        $request = $client->requests[0];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('http://request-catcher:5000/mock-sms', (string)$request->getUri());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('Appwrite Mock Message Sender', $request->getHeaderLine('User-Agent'));
        $this->assertEquals('username', $request->getHeaderLine('X-Username'));

        $body = \json_decode((string)$request->getBody(), true);
        $this->assertEquals('+987654321', $body['from']);
        $this->assertEquals('+123456789', $body['to']);
        $this->assertEquals('Test Content', $body['message']);
    }

    public function testNetworkErrorMapsToErrorResult(): void
    {
        $client = new MockClient();
        $client->queueException(new MockNetworkException(
            new \Utopia\Psr7\Request('POST', \Utopia\Psr7\Uri::parse('https://example.test/single')),
            'Connection refused',
            7,
        ));

        $adapter = new MultiAdapter(client: $client);

        $result = $adapter->sendOne(['n' => 0]);

        $this->assertEquals(0, $result['statusCode']);
        $this->assertNull($result['response']);
        $this->assertEquals('Connection refused', $result['error']);
        $this->assertEquals(7, $result['errorCode']);
    }

    public function testRequestMultiUsesInjectedClient(): void
    {
        $client = new MockClient();
        $client->queueResponse(200, '{"n":0}');
        $client->queueResponse(500, '{"n":1}');
        $client->queueResponse(200, '{"n":2}');

        $adapter = new MultiAdapter(client: $client);

        $results = $adapter->sendBatch([
            ['n' => 0],
            ['n' => 1],
            ['n' => 2],
        ]);

        $this->assertCount(3, $client->requests);
        $this->assertCount(3, $results);

        $byIndex = \array_column($results, null, 'index');
        $this->assertEquals([200, 500, 200], [
            $byIndex[0]['statusCode'],
            $byIndex[1]['statusCode'],
            $byIndex[2]['statusCode'],
        ]);

        foreach ($client->requests as $request) {
            $this->assertEquals('https://example.test/batch', (string)$request->getUri());
            $this->assertEquals('Appwrite Multi Message Sender', $request->getHeaderLine('User-Agent'));
        }
    }
}
