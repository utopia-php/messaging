<?php

namespace Utopia\Tests\Adapter\Chat;

use InvalidArgumentException;
use Utopia\Messaging\Adapter\Chat\Discord;
use Utopia\Messaging\Messages\Discord as DiscordMessage;
use Utopia\Tests\Adapter\Base;

class DiscordTest extends Base
{
    public function testSendMessage(): void
    {
        $url = \getenv('DISCORD_WEBHOOK_URL');

        $sender = new Discord($url);

        $content = 'Test Content';

        $message = new DiscordMessage(
            content: $content,
            wait: true
        );

        $result = $sender->send($message);

        $this->assertResponse($result);
    }

    /**
     * @return array<array<string>>
     */
    public static function invalidURLProvider(): array
    {
        return [
            'invalid URL format' => ['not-a-url'],
            'invalid scheme (http)' => ['http://discord.com/api/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz'],
            'invalid host' => ['https://example.com/api/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz'],
            'missing path' => ['https://discord.com'],
            'no webhooks segment' => ['https://discord.com/api/invalid/123456789012345678/token'],
            'missing webhook ID' => ['https://discord.com/api/webhooks//token'],
        ];
    }

    /**
     * @dataProvider invalidURLProvider
     */
    public function testInvalidURLs(string $invalidURL): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Discord($invalidURL);
    }

    public function testValidURLVariations(): void
    {
        // Valid URL format variations
        $validURLs = [
            'with api path' => 'https://discord.com/api/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz',
            'without api path' => 'https://discord.com/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz',
            'with trailing slash' => 'https://discord.com/api/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz/',
        ];

        foreach ($validURLs as $label => $url) {
            try {
                $discord = new Discord($url);
                // If we get here, the URL was accepted
                $this->assertTrue(true, "Valid URL variant '{$label}' was accepted as expected");
            } catch (InvalidArgumentException $e) {
                $this->fail("Valid URL variant '{$label}' was rejected: " . $e->getMessage());
            }
        }
    }

    public function testWebhookIDExtraction(): void
    {
        // Create a reflection of Discord to access protected properties
        $webhookId = '123456789012345678';
        $url = "https://discord.com/api/webhooks/{$webhookId}/abcdefghijklmnopqrstuvwxyz";

        $discord = new Discord($url);
        $reflector = new \ReflectionClass($discord);
        $property = $reflector->getProperty('webhookId');
        $property->setAccessible(true);

        $this->assertEquals($webhookId, $property->getValue($discord), 'Webhook ID was not correctly extracted');
    }
}
