<?php

declare(strict_types=1);

namespace Utopia\Tests\Adapter\SMS;

use PHPUnit\Framework\Attributes\Depends;
use Utopia\Messaging\Adapter\SMS\WhatsApp;
use Utopia\Messaging\Adapter\SMS\WhatsApp\MetadataParameter;
use Utopia\Messaging\Messages\SMS;
use Utopia\Tests\Adapter\Base;

/**
 * Runs against a Meta developer test number, which is free and can message up to
 * five allow-listed recipients. Skipped unless the TESTS_WHATSAPP_* variables
 * are set: a System User access token, the test Phone Number ID, the WhatsApp
 * Business Account ID that owns it, and an allow-listed recipient number.
 */
final class WhatsAppE2ETest extends Base
{
    private const string TEMPLATE = 'utopia_messaging_test';

    private const string LANGUAGE = 'en_US';

    private WhatsApp $adapter;

    private string $businessAccountId;

    private string $recipient;

    protected function setUp(): void
    {
        $accessToken = getenv('TESTS_WHATSAPP_ACCESS_TOKEN') ?: '';
        $phoneNumberId = getenv('TESTS_WHATSAPP_PHONE_NUMBER_ID') ?: '';
        $this->businessAccountId = getenv('TESTS_WHATSAPP_BUSINESS_ACCOUNT_ID') ?: '';
        $this->recipient = getenv('TESTS_WHATSAPP_RECIPIENT') ?: '';

        if ($accessToken === '' || $phoneNumberId === '' || $this->businessAccountId === '' || $this->recipient === '') {
            $this->markTestSkipped('Set TESTS_WHATSAPP_ACCESS_TOKEN, TESTS_WHATSAPP_PHONE_NUMBER_ID, TESTS_WHATSAPP_BUSINESS_ACCOUNT_ID and TESTS_WHATSAPP_RECIPIENT to run against Meta.');
        }

        $this->adapter = new WhatsApp($accessToken, $phoneNumberId, self::TEMPLATE, self::LANGUAGE);
    }

    public function testUpsertTemplateIsApprovedImmediately(): void
    {
        $result = $this->adapter->upsertTemplate($this->businessAccountId, [self::LANGUAGE], 10);

        $entries = $result['data'] ?? [$result];
        $this->assertNotEmpty($entries, 'Meta returned no template entry.');

        $languages = [];
        foreach ($entries as $entry) {
            $this->assertSame('APPROVED', $entry['status'] ?? null, 'Authentication templates are approved without review.');
            $languages[] = $entry['language'] ?? self::LANGUAGE;
        }

        $this->assertContains(self::LANGUAGE, $languages);
    }

    #[Depends('testUpsertTemplateIsApprovedImmediately')]
    public function testDeliversCodeToAllowListedRecipient(): void
    {
        $code = (string) random_int(100000, 999999);

        $response = $this->adapter->send(new SMS([$this->recipient], $code));

        $this->assertResponse($response);
        $this->assertSame($this->recipient, $response['results'][0]['recipient']);
    }

    #[Depends('testUpsertTemplateIsApprovedImmediately')]
    public function testReportsMissingTemplateLanguage(): void
    {
        $message = new SMS([$this->recipient], '123456');
        $message->setMetadata([MetadataParameter::LANGUAGE->value => 'zu']);

        $response = $this->adapter->send($message);

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertSame('failure', $response['results'][0]['status']);
        $this->assertStringContainsString('132001', (string) $response['results'][0]['error'], 'Meta reports a missing template translation as error 132001.');
    }

    public function testReportsRecipientOutsideAllowList(): void
    {
        $response = $this->adapter->send(new SMS(['+15550000001'], '123456'));

        $this->assertSame(0, $response['deliveredTo']);
        $this->assertSame('failure', $response['results'][0]['status']);
        $this->assertStringContainsString('131030', (string) $response['results'][0]['error'], 'A test number may only message allow-listed recipients.');
    }
}
