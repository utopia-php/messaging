<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://www.plivo.com/docs/sms/api/message#send-a-message
class Plivo extends SMSAdapter
{
    /**
     * @param  string  $authId Plivo Auth ID
     * @param  string  $authToken Plivo Auth Token
     */
    public function __construct(
        private string $authId,
        private string $authToken,
        private ?string $from = null
    ) {
    }

    public function getName(): string
    {
        return 'Plivo';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        return $this->request(
            method: 'POST',
            url: "https://api.plivo.com/v1/Account/{$this->authId}/Message/",
            headers: [
                'Authorization: Basic '.base64_encode("{$this->authId}:{$this->authToken}"),
            ],
            body: \http_build_query([
                'text' => $message->getContent(),
                'src' => $this->from ?? $message->getFrom() ?? 'Plivo',
                'dst' => \implode('<', $message->getTo()),
            ]),
        );
    }
}
