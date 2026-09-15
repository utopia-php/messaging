<?php

declare(strict_types=1);

namespace Utopia\Tests\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS\WhatsApp;
use Utopia\Messaging\Adapter\SMS\WhatsApp\MetadataParameter;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

/**
 * Runs against a Meta developer test number, which is free and can message up
 * to five allow-listed recipients. The test account ships with sample templates
 * only and refuses to create new ones, so there is no authentication template
 * to deliver a code with; these tests cover the real request path and Meta's
 * error responses instead, plus template lookup against the sample templates.
 * Requires TESTS_WHATSAPP_ACCESS_TOKEN, TESTS_WHATSAPP_PHONE_NUMBER_ID,
 * TESTS_WHATSAPP_BUSINESS_ACCOUNT_ID and an allow-listed TESTS_WHATSAPP_RECIPIENT.
 */
final class WhatsAppTest extends Base
{
    private const string TEMPLATE = 'utopia_messaging_test';

    /**
     * Utility template Meta ships with every test account.
     */
    private const string SAMPLE_TEMPLATE = 'hello_world';

    private string $accessToken;

    private string $phoneNumberId;

    private string $businessAccountId;

    private string $recipient;

    protected function setUp(): void
    {
        $this->accessToken = getenv('TESTS_WHATSAPP_ACCESS_TOKEN') ?: '';
        $this->phoneNumberId = getenv('TESTS_WHATSAPP_PHONE_NUMBER_ID') ?: '';
        $this->businessAccountId = getenv('TESTS_WHATSAPP_BUSINESS_ACCOUNT_ID') ?: '';
        $this->recipient = getenv('TESTS_WHATSAPP_RECIPIENT') ?: '';

        if ($this->accessToken === '' || $this->phoneNumberId === '' || $this->businessAccountId === '' || $this->recipient === '') {
            $this->markTestSkipped('Set TESTS_WHATSAPP_ACCESS_TOKEN, TESTS_WHATSAPP_PHONE_NUMBER_ID, TESTS_WHATSAPP_BUSINESS_ACCOUNT_ID and TESTS_WHATSAPP_RECIPIENT to run against Meta.');
        }
    }

    public function testListsTemplateLanguagesWithStatus(): void
    {
        $adapter = new WhatsApp($this->accessToken, $this->phoneNumberId, self::TEMPLATE);

        $templates = $adapter->getTemplate($this->businessAccountId, self::SAMPLE_TEMPLATE);

        $this->assertNotEmpty($templates, 'Every Meta test account ships the hello_world sample template.');
        $this->assertSame(self::SAMPLE_TEMPLATE, $templates[0]['name']);
        $this->assertSame('APPROVED', $templates[0]['status']);
        $this->assertSame('en_US', $templates[0]['language']);
    }

    public function testListsNothingForUnknownTemplate(): void
    {
        $adapter = new WhatsApp($this->accessToken, $this->phoneNumberId, self::TEMPLATE);

        $this->assertSame([], $adapter->getTemplate($this->businessAccountId));
    }

    public function testRefusesTemplateCreationOnTestAccount(): void
    {
        $adapter = new WhatsApp($this->accessToken, $this->phoneNumberId, self::TEMPLATE);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error 10');
        $adapter->upsertTemplate($this->businessAccountId, ['en_US']);
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

    public function testOverridesTemplateAndLanguagePerMessage(): void
    {
        $adapter = new WhatsApp($this->accessToken, $this->phoneNumberId, self::SAMPLE_TEMPLATE, 'fr');

        $message = new SMS([$this->recipient], '123456');
        $message->setMetadata([
            MetadataParameter::TEMPLATE->value => self::TEMPLATE,
            MetadataParameter::LANGUAGE->value => 'en_US',
            MetadataParameter::CALLBACK_DATA->value => 'utopia-messaging-e2e',
        ]);

        $response = $adapter->send($message);

        $this->assertSame('failure', $response['results'][0]['status']);
        $this->assertStringContainsString('132001', (string) $response['results'][0]['error'], 'The override, not the constructor template, must reach Meta.');
        $this->assertStringContainsString('en_US', (string) $response['results'][0]['error'], 'The language override must reach Meta.');
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
