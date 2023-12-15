<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
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
     */
    protected function process(SMSMessage $message): array
    {
        $response = new Response($this->getType());

        $result = $this->request(
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
        );

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            $response->setDeliveredTo(1);
            $response->addResultForRecipient($message->getTo()[0]);
        } else {
            $response->addResultForRecipient($message->getTo()[0], $result['response']['message'] ?? '');
        }

        return $response->toArray();
    }
}
