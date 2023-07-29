<?php

namespace Utopia\Messaging\Adapters\Push\Apple;

use Utopia\Messaging\Adapters\Push\Apple\Exception;
use Utopia\Messaging\Adapters\Push\Apple\Config;
use Utopia\Messaging\Adapters\Push\Apple\Message;
use Utopia\Messaging\Adapters\Push\Apple\Response;

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
    $url = $this->config->getURL() . $deviceToken;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config->getHeaders());
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message->toJson()));
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

    curl_setopt($ch, CURLOPT_SSLCERT, $this->config->getKeyPath());
    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->config->getSecretKey());

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