<?php

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\Msg91;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

class Msg91Test extends Base
{
    public function testSendSMS(): void
    {
        $sender = new Msg91(getenv('MSG_91_SENDER_ID'), getenv('MSG_91_AUTH_KEY'), getenv('MSG_91_TEMPLATE_ID'));

        $message = new SMS(
            to: [getenv('MSG_91_TO')],
            content: 'Test Content',
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
    }

    public function testSendSMSWithMetadata(): void
    {
        $sender = new Msg91TestAdapter('sender', 'auth', 'template');

        $message = new SMS(
            to: ['+911234567890'],
            content: 'Test Content',
            metadata: [
                'clientId' => 'client-123',
                'CRQID' => 'request_123',
                'UUID' => 'uuid.123',
                'ignored' => 'value',
            ],
        );

        $response = $sender->send($message);

        $this->assertResponse($response);
        $this->assertEquals('client-123', $sender->body['clientId']);
        $this->assertEquals('request_123', $sender->body['CRQID']);
        $this->assertEquals('uuid.123', $sender->body['UUID']);
        $this->assertArrayNotHasKey('ignored', $sender->body);
    }

    public function testSendSMSWithInvalidMetadata(): void
    {
        $sender = new Msg91TestAdapter('sender', 'auth', 'template');

        $message = new SMS(
            to: ['+911234567890'],
            content: 'Test Content',
            metadata: [
                'CRQID' => 'invalid value',
            ],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Msg91 CRQID metadata must be 80 characters or less');

        $sender->send($message);
    }
}

class Msg91TestAdapter extends Msg91
{
    /**
     * @var array<string, mixed>
     */
    public array $body = [];

    /**
     * @param  array<string>  $headers
     * @param  array<string, mixed>|null  $body
     * @return array{
     *     url: string,
     *     statusCode: int,
     *     response: array<string, mixed>|string|null,
     *     headers: array<string, string>,
     *     error: string|null
     * }
     */
    protected function request(
        string $method,
        string $url,
        array $headers = [],
        ?array $body = null,
        int $timeout = 30,
        int $connectTimeout = 10
    ): array {
        $this->body = $body ?? [];

        return [
            'url' => $url,
            'statusCode' => 200,
            'response' => [],
            'headers' => [],
            'error' => null,
        ];
    }
}
