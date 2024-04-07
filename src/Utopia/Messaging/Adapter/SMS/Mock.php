<?php

namespace Utopia\Messaging\Adapter\SMS;

use Exception;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class Mock extends SMSAdapter
{
    protected const NAME = 'Mock';

    /**
     * @param  string  $user User ID
     * @param  string  $secret User secret
     */
    public function __construct(
        private string $user,
        private string $secret
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
        $response = new Response($this->getType());

        $response->setDeliveredTo(\count($message->getTo()));

        $result = $this->request(
            method: 'POST',
            url: 'http://request-catcher:5000/mock-sms',
            headers: [
                'Content-Type: application/json',
                "X-Username: {$this->user}",
                "X-Key: {$this->secret}",
            ],
            body: [
                'message' => $message->getContent(),
                'from' => $message->getFrom(),
                'to' => \implode(',', $message->getTo()),
            ],
        );

        if ($result['statusCode'] === 200) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResult($to, 'Unknown Error.');
            }
        }

        return $response->toArray();
    }

    /**
     * @param string $phone
     * @return int|null
     * @throws Exception
     */
    public function getCountryCode(string $phone): ?int
    {
        if (empty($phone)) {
            throw new \Exception('No phone number was passed.');
        }

        $helper = PhoneNumberUtil::getInstance();

        try {
            return  $helper
                ->parse($phone)
                ->getCountryCode();

        } catch (NumberParseException $e) {
            throw new \Exception("Error parsing phone: " . $e->getMessage());
        }
    }
}
