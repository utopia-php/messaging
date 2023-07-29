<?php

namespace Utopia\Messaging\Adapters\Push\Apple;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Apple\Notification as ApplePushNotification;
use Utopia\Messaging\Messages\Push;

class ApplePushAdapter implements PushAdapter
{
    protected $client;

    public function __construct($certificatePath, $passphrase, $ssl = 'ssl://gateway.push.apple.com:2195')
    {
        $this->client = new ApplePushNotification($certificatePath, $passphrase, $ssl);
    }

    public function getName(): string
    {
        return 'applePush';
    }

    public function process(Push $message): string
    {
        try {
            $this->client->send($message->getTo(), $message->getBody());

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }
}

?>
