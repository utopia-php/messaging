<?php

namespace Utopia\Messaging\Adapter\SMS;

// Reference Material
// https://www.textmagic.com/docs/api/send-sms/#How-to-send-bulk-text-messages

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class TextMagic extends SMSAdapter
{
    protected const NAME = 'Textmagic';

    /**
     * @param  string  $username Textmagic account username
     * @param  string  $apiKey Textmagic account API key
     */
    public function __construct(
        private string $username,
        private string $apiKey,
        private ?string $from = null
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
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
    protected function process(SMSMessage $message): array
    {
        $to = \array_map(
            fn ($to) => \ltrim($to, '+'),
            $message->getTo()
        );

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: 'https://rest.textmagic.com/api/v2/messages',
            headers: [
                "X-TM-Username: {$this->username}",
                "X-TM-Key: {$this->apiKey}",
            ],
            body: \http_build_query([
                'text' => $message->getContent(),
                'from' => \ltrim($this->from ?? $message->getFrom(), '+'),
                'phones' => \implode(',', $to),
            ]),
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to, $result['response']['message'] ?? '');
            }
        }

        return $response->toArray();
    }
}
