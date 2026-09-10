<?php

namespace Test\E2E;

use Tests\E2E\Base;
use Utopia\Messaging\Messages\SMS;
use Utopia\Messaging\Adapters\SMS\MessageBird;


class MessageBirdTest extends Base {
  /**
   * @throws \Exception
   */
  public function testSendSMS() {
    $apiKey = getenv('MESSAGEBIRD_API_KEY');


    $sender = new MessageBird($apiKey);

    $message = new SMS(
      to: [getenv('MESSAGEBIRD_TO')],
      content: 'Test Content',
      from: getenv('MESSAGEBIRD_FROM')
    );

    $response = $sender->send($message);
    print_r($response);
    $result = \json_decode($response, true);

    $this->assertArrayHasKey('body', $result);
    $this->assertEquals('Test Content', $result['body']);
    $this->assertEquals(1, count($result['recipients']['items']));
  }
}
