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
     */


    public function __construct(
        private string $apikey
    ) {
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        // NOTE: user can do upto 1000 numbers per API call
        return 1000;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SMSMessage $message): array
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
                'number' => $message->getTo()[0],
                'message' => $message->getContent(),
                'sendername' => $message->getFrom()
            ],
        );

        if ($result['statusCode'] === 200) {
            if ($result['response'][0] && count($result['response'][0]) > 1) {
                $response->addResult($message->getTo()[0]);
            } else {
                foreach ($result['response'] as $variableName) {
                    $errorMessage = $variableName;
                    if (is_array($variableName)) {
                        $response->addResult($message->getTo()[0], $errorMessage[0]);
                    } else {
                        $response->addResult($message->getTo()[0], 'Unknown error');
                    }
                }
            }
        }
        if ($result['statusCode'] === 500) {
            $response->addResult($message->getTo()[0], $result['response'][0]);
        }

        return $response->toArray();
    }
}


// Below is a Sample Error response for bad payload
// The status code is 200 even if there is an error.
// The status code is 200 if semaphore returns a response irrespective of good or error response

// $errorResponse = array(
//     "url" => "https://api.semaphore.co/api/v4/messages",
//     "statusCode" => 200,
//     "response" => array(
//         "sendername" => array(
//             "The selected sendername is invalid."
//         )
//     ),
//     "error" => ""
// );



// Below is a Sample Error response when Semaphore credits are empty

// $errorResponse = [
//     "url" => "https://api.semaphore.co/api/v4/messages",
//     "statusCode" => 500,
//     "response" => [
//         "Your current balance of 0 credits is not sufficient. This transaction requires 1 credits."
//     ],
//     "error" => ""
// ];



// Below is a sample success response
// Unlike error response, More than 1 keys are returned in the response array. Refer to docs

// $successResponse = array(
//     "url" => "https://api.semaphore.co/api/v4/messages",
//     "statusCode" => 200,
//     "response" => array(
//         array(
//             "message_id" => 212210271,
//             "user_id" => 40495,
//             "user" => "hello@gmail.com",
//             "account_id" => 40356,
//             "account" => "Semiphore",
//             "recipient" => "639358574402",
//             "message" => "Nice meeting you",
//             "sender_name" => "Semaphore",
//             "network" => "Globe",
//             "status" => "Pending",
//             "type" => "Single",
//             "source" => "Api",
//             "created_at" => "2024-03-12 23:14:50",
//             "updated_at" => "2024-03-12 23:14:50"
//         )
//     ),
//     "error" => ""
// );
