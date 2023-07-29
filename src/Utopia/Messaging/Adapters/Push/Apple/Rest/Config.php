<?php

namespace Utopia\Messaging\Adapters\Push\Apple;

class Config
{
    public const PRODUCTION = "production";
    public const SANDBOX    = "sandbox";

    private $endpoint = [
      'production'  => 'https://api.push.apple.com/3/device/',
      'sandbox'     => 'https://api.development.push.apple.com/3/device/'
    ];

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $keypath;

    /**
     * @var string
     */
    private $topic;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var string
     */
    private $token;


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
    public function __construct($env = Config::SANDBOX, $keypath, $secretKey, $topic)
    {
        $this->url = $this->endpoint[$env];
        $this->keypath = $keypath;
        $this->secretKey = $secretKey;
        $this->topic = $topic;
    }

    /**
     * Get Secret key
     * 
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * Get Key path
     * 
     * @return string
     */
    public function getKeypath()
    {
        return $this->keypath;
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
     * Get Topic
     * 
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
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
        'apns-topic: ' . $this->getTopic(),
        'apns-push-type: ' . 'alert',
      ];
    }
}