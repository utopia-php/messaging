<?php

namespace Utopia\Push\Gcm;

use Utopia\Push\Gcm\Exception;
use Utopia\Push\Gcm\Config;
use Utopia\Push\Gcm\Message;

class Request
{
  /**
   * @var Config
   */
  private $config;

  /**
   * Construct.
   * 
   * @param Config $config
   */
  public function __construct(Config $config)
  {
    $this->config = $config;
  }

  public function send(string $device, Message $message)
  {
    $url = $config->getURL();

    $ch = curl_init($url);

    
    if (isset($this->proxy)) {
      curl_setopt($ch, CURLOPT_PROXY, $this->config->proxy);
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config->getHeaders());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message->toJson());

    $response = curl_exec($ch);

    curl_close($ch);

    return \json_decode($response, true);
  }
}