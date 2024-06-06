<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class SMSGatewayHub extends SMSAdapter
{
    protected const NAME = 'SMSGatewayHub';

    /**
     * @param  string  $apiKey SMS Gateway Hub API Key
     * @param  string  $senderId SMS Gateway Hub Sender ID
     * @param  string  $route SMS Gateway Hub Route
     * @param  string  $dltTemplateId SMS Gateway Hub DLT Template ID
     * @param  string  $peId SMS Gateway Hub PEID
     */
    public function __construct(
        private string $apiKey,
        private string $senderId,
        private string $route,
        private string $dltTemplateId,
        private string $peId,
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 100;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array
    {
        $recipients = [];
        foreach ($message->getTo() as $recipient) {
            $recipients[] = \ltrim($recipient, '+');
        }

        $response = new Response($this->getType());

        // Construct the query string
        $queryParams = http_build_query([
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

        // Log the URL and result for debugging
        \error_log('Request URL: ' . $url);
        \error_log('Response: ' . \json_encode($result));

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
