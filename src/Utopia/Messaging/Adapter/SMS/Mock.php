<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class Mock extends SMSAdapter
{
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
        return 'Mock';
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
                'content-type: application/json',
                "x-username: {$this->user}",
                "x-key: {$this->secret}",
            ],
            body: \json_encode([
                'message' => $message->getContent(),
                'from' => $message->getFrom(),
                'to' => \implode(',', $message->getTo()),
            ]),
        );

        if ($result['statusCode'] === 200) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to, 'Unknown Error.');
            }
        }

        return $response->toArray();
    }
}
