<?php

declare(strict_types=1);

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\WhatsApp;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

/**
 * Runs against a Meta developer test number, which is free and can message up
 * to five allow-listed recipients. The test account ships with sample templates
 * only and refuses to create new ones, so there is no authentication template
 * to deliver a code with; these tests cover the real request path and Meta's
 * error responses instead. Requires TESTS_WHATSAPP_ACCESS_TOKEN,
 * TESTS_WHATSAPP_PHONE_NUMBER_ID and an allow-listed TESTS_WHATSAPP_RECIPIENT.
 */
final class WhatsAppTest extends Base
{
    private const string TEMPLATE = 'utopia_messaging_test';

    private string $accessToken;

    private string $phoneNumberId;

    private string $recipient;

    protected function setUp(): void
    {
        $this->accessToken = getenv('TESTS_WHATSAPP_ACCESS_TOKEN') ?: '';
        $this->phoneNumberId = getenv('TESTS_WHATSAPP_PHONE_NUMBER_ID') ?: '';
        $this->recipient = getenv('TESTS_WHATSAPP_RECIPIENT') ?: '';

        if ($this->accessToken === '' || $this->phoneNumberId === '' || $this->recipient === '') {
            $this->markTestSkipped('Set TESTS_WHATSAPP_ACCESS_TOKEN, TESTS_WHATSAPP_PHONE_NUMBER_ID and TESTS_WHATSAPP_RECIPIENT to run against Meta.');
        }
    }

    public function testReportsMissingTemplate(): void
    {
        $adapter = new WhatsApp($this->accessToken, $this->phoneNumberId, self::TEMPLATE);

        $response = $adapter->send(new SMS([$this->recipient], '123456'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertSame($this->recipient, $response['results'][0]['recipient']);
        $this->assertSame('failure', $response['results'][0]['status']);
        $this->assertStringContainsString('132001', (string) $response['results'][0]['error'], 'Meta reports an unknown template as error 132001.');
    }

    public function testReportsRecipientOutsideAllowList(): void
    {
        $adapter = new WhatsApp($this->accessToken, $this->phoneNumberId, self::TEMPLATE);

        $response = $adapter->send(new SMS(['+15550000001'], '123456'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertSame('failure', $response['results'][0]['status']);
        $this->assertStringContainsString('131030', (string) $response['results'][0]['error'], 'A test number may only message allow-listed recipients.');
    }

    public function testReportsInvalidToken(): void
    {
        $adapter = new WhatsApp('not-a-token', $this->phoneNumberId, self::TEMPLATE);

        $response = $adapter->send(new SMS([$this->recipient], '123456'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertSame('failure', $response['results'][0]['status']);
        $this->assertStringContainsString('190', (string) $response['results'][0]['error'], 'Meta rejects a bad bearer token with error 190.');
    }
}
