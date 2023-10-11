<?php

namespace Tests\E2E;


use Utopia\Messaging\Adapters\Email\SparkPost;
use Utopia\Messaging\Messages\Email;

class SparkPostTest extends Base{
    /**
     * @throws \Exception
     */

     public function testSendEmail(){

        $apiKey = getenv('SPARKPOST_API_KEY');

        $to = getenv('SPARKPOST_TO');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = getenv('SPARKPOST_FROM');

        $sparkPost = new SparkPost($apiKey);
        $message = new Email(
            to: [$to],
            from: $from,
            subject: $subject,
            content: $content ,
        );

        $response = $sparkPost->send($message);
        $this->assertNotEmpty($response);
    }
}
?>