<?php

namespace Utopia\Messaging\Adapters\Push\Apple;

class Message
{
    /**
     * @var string
     */
    private $title;  

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $sound;

    public function __construct($title, $body, $sound = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->sound = $sound;
    }

    public function toJson()
    {
      return json_encode([
        'aps' => [
          'alert' => [
            'title' => $this->title,
            'body' => $this->body
          ],
          'sound' => $this->sound
        ]
      ]);
    }
}