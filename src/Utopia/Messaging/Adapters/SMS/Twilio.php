<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Response;

class Twilio extends SMSAdapter
{
    /**
     * @param  string  $accountSid Twilio Account SID
     * @param  string  $authToken Twilio Auth Token
     */
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private ?string $from = null
    ) {
    }

    public function getName(): string
    {
        return 'Twilio';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $response = new Response($this->getType());

        $result = \json_decode($this->request(
            method: 'POST',
            url: "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json",
            headers: [
                'Authorization: Basic '.base64_encode("{$this->accountSid}:{$this->authToken}"),
            ],
            body: \http_build_query([
                'Body' => $message->getContent(),
                'From' => $this->from ?? $message->getFrom(),
                'To' => $message->getTo()[0],
            ]),
        ), true);

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->setDeliveredTo(1);
            $response->addToDetails($message->getTo()[0]);
        } else {
            $response->addToDetails($message->getTo()[0], $result['body']['message'] ?? '');
        }

        return \json_encode($response->toArray());
    }
}
