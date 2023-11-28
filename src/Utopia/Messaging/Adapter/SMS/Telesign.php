<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;

// Reference Material
// https://developer.telesign.com/enterprise/reference/sendbulksms

class Telesign extends SMSAdapter
{
    /**
     * @param  string  $username Telesign account username
     * @param  string  $password Telesign account password
     */
    public function __construct(
        private string $username,
        private string $password
    ) {
    }

    public function getName(): string
    {
        return 'Telesign';
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
    protected function process(SMSMessage $message): string
    {
        $to = $this->formatNumbers(\array_map(
            fn ($to) => $to,
            $message->getTo()
        ));

        $result = $this->request(
            method: 'POST',
            url: 'https://rest-ww.telesign.com/v1/verify/bulk_sms',
            headers: [
                'Authorization: Basic '.base64_encode("{$this->username}:{$this->password}"),
            ],
            body: \http_build_query([
                'template' => $message->getContent(),
                'recipients' => $to,
            ]),
        );

        return \json_encode($result['response']);
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
