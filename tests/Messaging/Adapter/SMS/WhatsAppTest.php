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

    public function testSendsCodeInBodyAndButton(): void
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
        $this->assertSame('https://graph.facebook.com/v26.0/' . self::PHONE_NUMBER_ID . '/messages', (string) $request->getUri());
        $this->assertSame('Bearer ' . self::TOKEN, $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('Appwrite WhatsApp Message Sender', $request->getHeaderLine('User-Agent'));

        $this->assertSame([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => '14155551234',
            'type' => 'template',
            'template' => [
                'name' => self::TEMPLATE,
                'language' => ['code' => 'en_US'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => '482913']],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => [['type' => 'text', 'text' => '482913']],
                    ],
                ],
            ],
        ], $client->body());
    }

    public function testUsesConstructorLanguageAndVersion(): void
    {
        $client = new RecordingClient(200, ['messages' => [['id' => 'wamid.1']]]);
        $adapter = new WhatsApp(self::TOKEN, self::PHONE_NUMBER_ID, self::TEMPLATE, 'pt_BR', 'v23.0', $this->factory($client));

        $adapter->send(new SMS(['+5511987654321'], '123456'));

        $this->assertInstanceOf(RequestInterface::class, $client->request);
        $this->assertStringStartsWith('https://graph.facebook.com/v23.0/', (string) $client->request->getUri());
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
        $this->assertSame('https://graph.facebook.com/v26.0/102290129340398/upsert_message_templates', (string) $client->request->getUri());
        $this->assertSame('Bearer ' . self::TOKEN, $client->request->getHeaderLine('Authorization'));
        $this->assertSame([
            'name' => self::TEMPLATE,
            'category' => 'authentication',
            'components' => [
                ['type' => 'body', 'add_security_recommendation' => true],
                ['type' => 'footer', 'code_expiration_minutes' => 10],
                ['type' => 'buttons', 'buttons' => [['type' => 'otp', 'otp_type' => 'copy_code']]],
            ],
            'languages' => ['en_US', 'fr'],
            'message_send_ttl_seconds' => 600,
        ], $client->body());
    }

    public function testUpsertsTemplateWithoutOptionalParts(): void
    {
        $client = new RecordingClient(200, ['data' => []]);
        $adapter = $this->adapter($client);

        $adapter->upsertTemplate('102290129340398', securityRecommendation: false);

        $this->assertSame([
            'name' => self::TEMPLATE,
            'category' => 'authentication',
            'components' => [
                ['type' => 'body', 'add_security_recommendation' => false],
                ['type' => 'buttons', 'buttons' => [['type' => 'otp', 'otp_type' => 'copy_code']]],
            ],
        ], $client->body());
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
