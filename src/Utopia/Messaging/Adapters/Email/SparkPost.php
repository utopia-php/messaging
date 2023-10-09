<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class SparkPost extends EmailAdapter{

    public function __construct(private string $apiKey, private string $senderDomain,
    private bool $isEu = false){
    }

    public function getName() :string{
        return 'SparkPost';
    } 

    public function getMaxMessagesPerRequest(): int{
        //TODO: real value for this
        return 1000;
    }

    protected function process(Email $message) : string{
        
        $usDomain = 'api.sparkpost.com';
        $euDomain = 'api.eu.sparkpost.com';

        $domain = $this->isEu ? $euDomain : $usDomain;

        $requestBody = [
            'options' =>[
                //for testing without domain set sandbox option to true(for more information, visit sparkpost.com)
                'sandbox' => false,
            ],
            'recipients' => [
                [
                    'address' => [
                        'email' => $message->getTo()[0], // Assuming you have only one recipient
                    ],
                ],
            ],
            'content' => [
                'from' => [
                    //sender domain should be registered and verified at sparkpost.com 
                    'email' => $this->senderDomain,
                ],
                'subject' => $message->getSubject(),
                'html' => $message->isHtml() ? $message->getContent() : null,
                'text' => $message->isHtml() ? null : $message->getContent(),
            ],
        ];
        
        $json_body = json_encode($requestBody);

        $response = $this->request(
            method: 'POST',
            url: "https://$domain/api/v1/transmissions",
            headers :[
                'Authorization: Basic '.$this->apiKey,
            ],
            body: $json_body,
        );

        return $response;
    }
}

?>