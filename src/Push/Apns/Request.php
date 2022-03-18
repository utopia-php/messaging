<?php

namespace Utopia\Push\Apns;

use Utopia\Push\Apns\Exception;
use Utopia\Push\Apns\Config;
use Utopia\Push\Apns\Message;
use Utopia\Push\Apns\Response;

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

  public function send(string $deviceToken, Message $message)
  {
    $url = $config->getURL() . $deviceToken;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $config->getHeaders());
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message->toJson()));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

    curl_setopt($ch, CURLOPT_SSLCERT, $config->getKeyPath());
    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $config->getSecretKey());

    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_exec($ch);
    
    $response = new Response(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    curl_close($ch);

    return [
      'code' => $response->code,
      'response' => $response->description
    ];
  }
}