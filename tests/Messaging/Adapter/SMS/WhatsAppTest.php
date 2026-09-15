<?php

declare(strict_types=1);

namespace Utopia\Tests\Adapter\SMS;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Utopia\Messaging\Adapter\SMS\WhatsApp;
use Utopia\Messaging\Adapter\SMS\WhatsApp\MetadataParameter;
use Utopia\Messaging\Messages\SMS;
use Utopia\Psr7\Response;
use Utopia\Psr7\Stream\Factory as StreamFactory;
use Utopia\Tests\Adapter\Base;

final class WhatsAppTest extends Base
{
    private const string TOKEN = 'EAAG-system-user-token';

    private const string PHONE_NUMBER_ID = '106540352242922';

    private const string TEMPLATE = 'appwrite_otp';

    public function testDeliversCodeThroughTemplate(): void
    {
        $client = new RecordingClient(200, [
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '14155551234', 'wa_id' => '14155551234']],
            'messages' => [['id' => 'wamid.HBgL', 'message_status' => 'accepted']],
        ]);
        $adapter = $this->adapter($client);

        $response = $adapter->send(new SMS(['+1 (415) 555-1234'], '482913'));

        $this->assertResponse($response);
        $this->assertSame('+1 (415) 555-1234', $response['results'][0]['recipient']);

        $request = $client->request;
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringContainsString('/' . self::PHONE_NUMBER_ID . '/messages', $request->getUri()->getPath());
        $this->assertSame('Bearer ' . self::TOKEN, $request->getHeaderLine('Authorization'));

        $body = $client->body();
        $this->assertSame('14155551234', $body['to'], 'Recipient must reach Meta as digits only.');
        $this->assertSame(self::TEMPLATE, $body['template']['name']);
        $this->assertSame('en_US', $body['template']['language']['code']);
        $this->assertSame(['482913', '482913'], $this->codes($body), 'The code must fill both the body and the button.');
    }

    public function testUsesConstructorLanguageAndVersion(): void
    {
        $client = new RecordingClient(200, ['messages' => [['id' => 'wamid.1']]]);
        $adapter = new WhatsApp(self::TOKEN, self::PHONE_NUMBER_ID, self::TEMPLATE, 'pt_BR', 'v23.0', $this->factory($client));

        $adapter->send(new SMS(['+5511987654321'], '123456'));

        $this->assertInstanceOf(RequestInterface::class, $client->request);
        $this->assertStringStartsWith('/v23.0/', $client->request->getUri()->getPath());
        $this->assertSame('pt_BR', $client->body()['template']['language']['code']);
    }

    public function testMetadataOverridesLanguage(): void
    {
        $client = new RecordingClient(200, ['messages' => [['id' => 'wamid.1']]]);
        $adapter = $this->adapter($client);

        $message = new SMS(['+33612345678'], '123456');
        $message->setMetadata([MetadataParameter::LANGUAGE->value => 'fr']);
        $adapter->send($message);

        $this->assertSame('fr', $client->body()['template']['language']['code']);
    }

    public function testRejectsEmptyLanguageMetadata(): void
    {
        $adapter = $this->adapter(new RecordingClient(200, []));

        $message = new SMS(['+33612345678'], '123456');
        $message->setMetadata([MetadataParameter::LANGUAGE->value => '']);

        $this->expectException(\InvalidArgumentException::class);
        $adapter->send($message);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'too long' => ['1234567890123456'];
        yield 'free text' => ['Your code is 123456'];
        yield 'newline' => ["123\n456"];
        yield 'symbols' => ['12-34'];
    }

    #[DataProvider('invalidCodes')]
    public function testRejectsContentThatIsNotACode(string $content): void
    {
        $client = new RecordingClient(200, []);
        $adapter = $this->adapter($client);

        try {
            $adapter->send(new SMS(['+14155551234'], $content));
            $this->fail('Expected an InvalidArgumentException.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('15 letters and digits', $exception->getMessage());
        }

        $this->assertNotInstanceOf(RequestInterface::class, $client->request, 'No request must be sent for an invalid code.');
    }

    public function testAcceptsAlphanumericCodeAtMaximumLength(): void
    {
        $client = new RecordingClient(200, ['messages' => [['id' => 'wamid.1']]]);
        $adapter = $this->adapter($client);

        $response = $adapter->send(new SMS(['+14155551234'], 'ABC123XYZ789ABC'));

        $this->assertResponse($response);
    }

    public function testReportsGraphApiErrorWithCode(): void
    {
        $client = new RecordingClient(400, [
            'error' => [
                'message' => '(#132001) Template name does not exist in the translation',
                'type' => 'OAuthException',
                'code' => 132001,
                'error_data' => [
                    'messaging_product' => 'whatsapp',
                    'details' => 'template name (appwrite_otp) does not exist in fr',
                ],
                'fbtrace_id' => 'AbC',
            ],
        ]);
        $adapter = $this->adapter($client);

        $response = $adapter->send(new SMS(['+33612345678'], '123456'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertSame('failure', $response['results'][0]['status']);
        $this->assertSame(
            'Error 132001: (#132001) Template name does not exist in the translation: template name (appwrite_otp) does not exist in fr',
            $response['results'][0]['error'],
        );
    }

    public function testReportsUndeliverableRecipient(): void
    {
        $client = new RecordingClient(400, [
            'error' => [
                'message' => 'Message Undeliverable',
                'code' => 131026,
                'error_data' => ['details' => 'Message Undeliverable'],
            ],
        ]);
        $adapter = $this->adapter($client);

        $response = $adapter->send(new SMS(['+14155551234'], '123456'));

        $this->assertSame('Error 131026: Message Undeliverable', $response['results'][0]['error']);
    }

    public function testReportsInvalidToken(): void
    {
        $client = new RecordingClient(401, [
            'error' => ['message' => 'Invalid OAuth access token', 'code' => 190],
        ]);
        $adapter = $this->adapter($client);

        $response = $adapter->send(new SMS(['+14155551234'], '123456'));

        $this->assertSame('Error 190: Invalid OAuth access token', $response['results'][0]['error']);
    }

    public function testReportsUnexpectedBody(): void
    {
        $client = new RecordingClient(502, 'Bad Gateway');
        $adapter = $this->adapter($client);

        $response = $adapter->send(new SMS(['+14155551234'], '123456'));

        $this->assertSame('Unknown error', $response['results'][0]['error']);
    }

    public function testReportsTransportFailure(): void
    {
        $client = new RecordingClient(0, null, new TransportException('Could not resolve host'));
        $adapter = $this->adapter($client);

        $response = $adapter->send(new SMS(['+14155551234'], '123456'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertSame('Could not resolve host', $response['results'][0]['error']);
    }

    public function testRefusesMoreThanOneRecipient(): void
    {
        $adapter = $this->adapter(new RecordingClient(200, []));

        $this->expectExceptionMessage('WhatsApp can only send 1 messages per request.');
        $adapter->send(new SMS(['+1', '+2'], '123456'));
    }

    public function testRefusesMissingRecipient(): void
    {
        $client = new RecordingClient(200, []);
        $adapter = $this->adapter($client);

        try {
            $adapter->send(new SMS([], '123456'));
            $this->fail('Expected an InvalidArgumentException.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('exactly one recipient', $exception->getMessage());
        }

        $this->assertNotInstanceOf(RequestInterface::class, $client->request, 'No request must be sent without a recipient.');
    }

    public function testUpsertsTemplateInEveryLanguage(): void
    {
        $client = new RecordingClient(200, [
            'data' => [
                ['id' => '1', 'status' => 'APPROVED', 'language' => 'en_US'],
                ['id' => '2', 'status' => 'APPROVED', 'language' => 'fr'],
            ],
        ]);
        $adapter = $this->adapter($client);

        $result = $adapter->upsertTemplate('102290129340398', ['en_US', 'fr'], 10, true, 600);

        $this->assertCount(2, $result['data']);
        $this->assertInstanceOf(RequestInterface::class, $client->request);
        $this->assertStringContainsString('/102290129340398/', $client->request->getUri()->getPath());
        $this->assertSame('Bearer ' . self::TOKEN, $client->request->getHeaderLine('Authorization'));

        $body = $client->body();
        $this->assertSame(self::TEMPLATE, $body['name']);
        $this->assertSame('authentication', $body['category']);
        $this->assertSame(['en_US', 'fr'], $body['languages']);
        $this->assertSame(600, $body['message_send_ttl_seconds']);
        $this->assertSame(10, $this->component($body, 'footer')['code_expiration_minutes']);
        $this->assertTrue($this->component($body, 'body')['add_security_recommendation']);
    }

    public function testUpsertsTemplateWithoutOptionalParts(): void
    {
        $client = new RecordingClient(200, ['data' => []]);
        $adapter = $this->adapter($client);

        $adapter->upsertTemplate('102290129340398', securityRecommendation: false);

        $body = $client->body();
        $this->assertArrayNotHasKey('languages', $body, 'Omitting languages lets Meta create every supported one.');
        $this->assertArrayNotHasKey('message_send_ttl_seconds', $body);
        $this->assertNull($this->component($body, 'footer'));
        $this->assertFalse($this->component($body, 'body')['add_security_recommendation']);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function validTimeToLive(): iterable
    {
        yield 'day' => [WhatsApp::TIME_TO_LIVE_DAY];
        yield 'minimum' => [WhatsApp::TIME_TO_LIVE_MIN_SECONDS];
        yield 'maximum' => [WhatsApp::TIME_TO_LIVE_MAX_SECONDS];
    }

    #[DataProvider('validTimeToLive')]
    public function testUpsertAcceptsTimeToLiveBoundaries(int $timeToLive): void
    {
        $client = new RecordingClient(200, ['data' => []]);
        $adapter = $this->adapter($client);

        $adapter->upsertTemplate('102290129340398', ['en_US'], timeToLive: $timeToLive);

        $this->assertSame($timeToLive, $client->body()['message_send_ttl_seconds']);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidTimeToLive(): iterable
    {
        yield 'zero' => [0];
        yield 'below minimum' => [WhatsApp::TIME_TO_LIVE_MIN_SECONDS - 1];
        yield 'above maximum' => [WhatsApp::TIME_TO_LIVE_MAX_SECONDS + 1];
        yield 'other negative' => [-2];
    }

    #[DataProvider('invalidTimeToLive')]
    public function testUpsertRejectsTimeToLiveOutOfRange(int $timeToLive): void
    {
        $client = new RecordingClient(200, ['data' => []]);
        $adapter = $this->adapter($client);

        try {
            $adapter->upsertTemplate('102290129340398', ['en_US'], timeToLive: $timeToLive);
            $this->fail('Expected an InvalidArgumentException.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('time to live', $exception->getMessage());
        }

        $this->assertNotInstanceOf(RequestInterface::class, $client->request, 'No request must be sent for an invalid time to live.');
    }

    public function testUpsertRejectsExpirationOutOfRange(): void
    {
        $adapter = $this->adapter(new RecordingClient(200, []));

        $this->expectException(\InvalidArgumentException::class);
        $adapter->upsertTemplate('102290129340398', ['en_US'], 91);
    }

    public function testUpsertThrowsOnGraphApiError(): void
    {
        $client = new RecordingClient(400, [
            'error' => ['message' => 'Unsupported post request', 'code' => 100],
        ]);
        $adapter = $this->adapter($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error 100: Unsupported post request');
        $adapter->upsertTemplate('bad-id', ['en_US']);
    }

    /**
     * Every text parameter in the template, in component order.
     *
     * @param  array<string, mixed>  $body
     * @return array<string>
     */
    private function codes(array $body): array
    {
        $codes = [];
        foreach ($body['template']['components'] as $component) {
            foreach ($component['parameters'] as $parameter) {
                $codes[] = $parameter['text'];
            }
        }

        return $codes;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    private function component(array $body, string $type): ?array
    {
        foreach ($body['components'] as $component) {
            if ($component['type'] === $type) {
                return $component;
            }
        }

        return null;
    }

    private function adapter(RecordingClient $client): WhatsApp
    {
        return new WhatsApp(self::TOKEN, self::PHONE_NUMBER_ID, self::TEMPLATE, clientFactory: $this->factory($client));
    }

    private function factory(RecordingClient $client): \Closure
    {
        return static fn(): ClientInterface => $client;
    }
}

final class RecordingClient implements ClientInterface
{
    public ?RequestInterface $request = null;

    /**
     * @param array<string, mixed>|string|null $response
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array|string|null $response,
        private readonly ?ClientExceptionInterface $exception = null,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        if ($this->exception instanceof ClientExceptionInterface) {
            throw $this->exception;
        }

        $body = \is_array($this->response) ? json_encode($this->response, JSON_THROW_ON_ERROR) : (string) $this->response;

        return new Response($this->statusCode, '', new StreamFactory()->createStream($body));
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        if (!$this->request instanceof RequestInterface) {
            throw new \LogicException('No request recorded.');
        }

        return json_decode((string) $this->request->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}

final class TransportException extends \RuntimeException implements ClientExceptionInterface {}
