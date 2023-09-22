<?php

namespace Utopia\Messaging\Adapters\Push;

use Utopia\Messaging\Adapters\Push as PushAdapter;
use Utopia\Messaging\Messages\Push;

class FCM extends PushAdapter
{
    /**
     * @param  string  $serverKey The FCM server key.
     */
    public function __construct(
        private string $serverKey,
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'FCM';
    }

    /**
     * Get max messages per request.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 5000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(Push $message): string
    {
      $payload = [
        'notification' => [
            'title' => $message->getTitle(),
            'body' => $message->getBody(),
            'click_action' => $message->getAction(),
            'icon' => $message->getIcon(),
            'badge' => $message->getBadge(),
            'color' => $message->getColor(),
            'sound' => $message->getSound(),
            'tag' => $message->getTag(),
        ],
        'data' => $message->getData(),
      ];
      
      return \json_encode($this->notify($message->getTo(), $payload));
      
        // return $this->request(
        //     method: 'POST',
        //     url: 'https://fcm.googleapis.com/fcm/send',
        //     headers: [
        //         'Content-Type: application/json',
        //         "Authorization: key={$this->serverKey}",
        //     ],
        //     body: \json_encode([
        //         'registration_ids' => $message->getTo(),
        //         'notification' => [
        //             'title' => $message->getTitle(),
        //             'body' => $message->getBody(),
        //             'click_action' => $message->getAction(),
        //             'icon' => $message->getIcon(),
        //             'badge' => $message->getBadge(),
        //             'color' => $message->getColor(),
        //             'sound' => $message->getSound(),
        //             'tag' => $message->getTag(),
        //         ],
        //         'data' => $message->getData(),
        //     ])
        // );
    }

    private function notify(array $to, array $payload): array
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = [
          'Content-Type: application/json',
          "Authorization: key={$this->serverKey}",
        ];

        $tokenChunks = array_chunk($to, 1000);

        $sh = curl_share_init();
        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);

        $mh = curl_multi_init();

        $channels = [];

        foreach ($tokenChunks as $tokens) {
          $ch = curl_init();
      
          $payload['registration_ids'] = $tokens;

          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
          curl_setopt($ch, CURLOPT_SHARE, $sh);
      
          curl_multi_add_handle($mh, $ch);
      
          $channels[] = $ch;
      }

      $running = null;
      do {
          curl_multi_exec($mh, $running);
          curl_multi_select($mh);
      } while ($running > 0);
      
      $results = [];

      foreach ($channels as $ch) {
        $results[] = [
          'statusCode' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
          'response'   => json_decode(curl_multi_getcontent($ch), true)  // Decoding JSON response to an associative array
        ];
        
        curl_multi_remove_handle($mh, $ch);
      }
      
      curl_multi_close($mh);
      curl_share_close($sh);

      return $results;
  }
}
