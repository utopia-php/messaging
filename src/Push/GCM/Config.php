<?php

namespace Utopia\Push\Gcm;

class Config
{
    private $url = 'https://android.googleapis.com/gcm/send';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $proxy;

    /**
     * Construct
     * 
     * Construct a new APNS push configuration.
     * 
     * @param string $env
     * @param string $keypath
     * @param string $secretKey
     * @param string $topic
     */
    public function __construct($apiKey, $proxy = null)
    {
        $this->apiKey = $apiKey;
        $this->proxy = $proxy;
    }

    /**
     * Get URL
     * 
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Returns headers used for sending notification.
     * 
     * @return associative array
     * 
     * @todo: add support for various push types.
     */
    public function getHeaders() {
      return [
        'Authorization: key=' . $this->apiKey,
        'Content-Type: application/json'
      ];
    }
}