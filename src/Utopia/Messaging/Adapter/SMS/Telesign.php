<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

// Reference Material
// https://developer.telesign.com/enterprise/reference/sendbulksms

class Telesign extends SMSAdapter
{
    protected const NAME = 'Telesign';

    /**
     * @param  string  $customerId Telesign customer ID
     * @param  string  $apiKey Telesign API key
     */
    public function __construct(
        private string $customerId,
        private string $apiKey
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
        $to = $this->formatNumbers(\array_map(
            fn ($to) => $to,
            $message->getTo()
        ));

        $response = new Response($this->getType());

        $result = $this->request(
            method: 'POST',
            url: 'https://rest-ww.telesign.com/v1/verify/bulk_sms',
            headers: [
                'Authorization: Basic '.base64_encode("{$this->customerId}:{$this->apiKey}"),
            ],
            body: \http_build_query([
                'template' => $message->getContent(),
                'recipients' => $to,
            ]),
        );

        if ($result['statusCode'] === 200) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                if (!\is_null($result['response']['errors'][0]['description'] ?? null)) {
                    $response->addResult($to, $result['response']['errors'][0]['description']);
                } else {
                    $response->addResult($to, 'Unknown error');
                }
            }
        }

        return $response->toArray();
    }

    /**
     * @param  array<string>  $numbers
     */
    private function formatNumbers(array $numbers): string
    {
        $formatted = \array_map(
            fn ($number) => $number.':'.\uniqid(),
            $numbers
        );

        return implode(',', $formatted);
    }
}
