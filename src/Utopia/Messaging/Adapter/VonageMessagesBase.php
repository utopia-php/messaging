<?php

namespace Utopia\Messaging\Adapter;

/**
 * Trait for Vonage Messages API adapters.
 *
 * Provides common functionality for adapters using the Vonage Messages API (V1).
 * This trait can be used by different message type adapters (SMS, Chat, etc.).
 *
 * Required properties (from extending class):
 * - $apiKey: string
 * - $apiSecret: string
 *
 * Reference: https://developer.vonage.com/en/api/messages
 */
trait VonageMessagesBase
{
    /**
     * Get the API endpoint for the Vonage Messages API.
     */
    protected function getApiEndpoint(): string
    {
        return 'https://api.nexmo.com/v1/messages';
    }

    protected function getAuthorizationHeader(): string
    {
        return 'Basic ' . \base64_encode("{$this->apiKey}:{$this->apiSecret}");
    }

    /**
     * Build the request headers for the Messages API.
     *
     * @return array<string>
     */
    protected function getRequestHeaders(): array
    {
        return [
            "Authorization: {$this->getAuthorizationHeader()}",
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }
}
