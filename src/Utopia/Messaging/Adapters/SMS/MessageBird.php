<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

class MessageBird extends SMSAdapter {
  /**
   * @param  string  $authToken MessageBird Auth Token
   */
  public function __construct(
    private string $authToken,
  ) {
  }

  public function getName(): string {
    return 'MessageBird';
  }

  public function getMaxMessagesPerRequest(): int {
    return 50;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function process(SMS $message): string {
    $to = \array_map(
      fn ($to) => \ltrim($to, '+'),
      $message->getTo()
    );

    return $this->request(
      method: 'POST',
      url: "https://rest.messagebird.com/messages",
      headers: [
        'Authorization: AccessKey ' . $this->authToken,
        'Content-Type: application/json',
      ],
      body: \json_encode([
        "recipients" => $to,
        "originator" => $message->getFrom(),
        "body" => $message->getContent(),

      ])
    );
  }
}
