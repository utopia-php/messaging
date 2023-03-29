<?php


namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://docs.clickatell.com/channels/sms-channels/sms-api-reference/#tag/SMS-API/operation/sendMessageREST_1
class Clickatell extends SMSAdapter {
 /**
  * @param string $apiKey Clickatell API Key
  */
 public function __construct(
  private string $apiKey,
 ) {
 }

 public function getName(): string {
  return 'Clickatell';
 }

 public function getMaxMessagesPerRequest(): int {
  return 1000;
 }

 /**
  * {@inheritdoc}
  *
  * @throws \Exception
  */
 protected function process(SMS $message): string {

  return $this->request(
   method: 'POST',
   url: "https://platform.clickatell.com/messages",
   headers: [
    'content-type: application/json',
    'Authorization: ' . $this->apiKey,
   ],
   body: \json_encode([
    'content' => $message->getContent(),
    'from'    => $message->getFrom(),
    'to'      => $message->getTo(),
   ]),
  );
 }
}
