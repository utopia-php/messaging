<?php


namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://www.infobip.com/docs/api/channels/sms/sms-messaging/outbound-sms/send-sms-message
class Infobip extends SMSAdapter {
 /**
  * @param string $apiBaseUrl Infobip API Base Url
  * @param string $apiKey     Infobip API Key
  */
 public function __construct(
  private string $apiBaseUrl,
  private string $apiKey
 ) {
 }

 public function getName(): string {
  return 'Infobip';
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
  $to = \array_map(fn($number) => ['to' => \ltrim($number, '+')], $message->getTo());

  return $this->request(
   method: 'POST',
   url: "https://{$this->apiBaseUrl}/sms/2/text/advanced",
   headers: [
    'Authorization: App ' . $this->apiKey,
    'content-type: application/json',
   ],
   body: \json_encode([
    'messages' => [
     'text'         => $message->getContent(),
     'from'         => $message->getFrom(),
     'destinations' => $to,
    ],
   ]),
  );
 }
}
