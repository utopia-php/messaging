<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class SmsGatewayHub extends SMSAdapter
{
    protected const NAME = 'SMSGatewayHub';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId,
        private readonly string $route,
        private readonly string $dltTemplateId,
        private readonly string $peId,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 100;
    }

    protected function process(SMSMessage $message): array
    {
        $recipients = [];
        foreach ($message->getTo() as $recipient) {
            $recipients[] = \ltrim($recipient, '+');
        }

        $response = new Response($this->getType());

        $queryParams = \http_build_query([
            'apiKey' => $this->apiKey,
            'senderid' => $this->senderId,
            'channel' => 'Trans',
            'DCS' => '0',
            'flashsms' => '0',
            'number' => \implode(',', $recipients),
            'text' => $message->getContent(),
            'route' => $this->route,
            'DLTTemplateId' => $this->dltTemplateId,
            'PEID' => $this->peId,
        ]);

        $url = 'https://login.smsgatewayhub.com/api/mt/SendSMS?' . $queryParams;
        $result = $this->request(
            'GET',
            $url,
            [
                'Content-Type: application/json',
            ]
        );

        if ($result['statusCode'] === 200) {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to);
            }
        } else {
            foreach ($message->getTo() as $to) {
                $response->addResult($to, 'Unknown error');
            }
        }

        return $response->toArray();
    }
}
