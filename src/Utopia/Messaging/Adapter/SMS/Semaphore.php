<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

// Reference Material
// https://semaphore.co/docs

class Semaphore extends SMSAdapter
{
    protected const NAME = 'Semaphore';

        /**
     * @param  string  $apikey Semaphore api key
     * @param  string  $number recipient number(s). If multiple numnbers, split with comma.
     * @param  string  $message message you want to send
     * @param  string  $sendername What your recipient sees as the sender of your message
     */

     
    public function __construct(
        private string $apikey,
        private string $number,
        private string $message,
        private string $senderName
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        // TODO: Find real limit
        return 25;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array
    {
     

        $response = new Response($this->getType());
        $result = $this->request(
            method: 'POST',
            url: 'https://api.semaphore.co/api/v4/messages',
            headers: [
                'Content-Type: application/json',
            ],
            body: [
                'apikey' => $this->apikey,
                'number' => $this->number,
                'message' => $this->message,
                'senderName' => $this->senderName
            ],
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


// The following lines of code are used to instantiate an object for the above class
$apikey='jkjdkfafd';
$number = '07358574402';
$message = 'Nice meeting you';
$senderName = 'Chaitanya';


$semaphore = new Semaphore($apikey, $number, $message, $senderName);


echo $semaphore;
