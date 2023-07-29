<?php

namespace Utopia\Messaging\Adapters\Push\Apple;

class Response
{
  private static $descriptions = [
    '0'   => 'Invalid configuration.',
    '200' => 'Success.',
    '400' => 'Bad request.',
    '403' => 'There was an error with the certificate or with the provider’s authentication token.',
    '404' => 'The request contained an invalid :path value.',
    '405' => 'The request used an invalid :method value. Only POST requests are supported.',
    '410' => 'The device token is no longer active for the topic.',
    '413' => 'The notification payload was too large.',
    '429' => 'The server received too many requests for the same device token.',
    '500' => 'Internal server error.',
    '503' => 'The server is shutting down and unavailable.',
  ];

  /**
   * @var string
   */
  public $code;
  
  /**
   * @var string
   */
  public $description;

  public function __construct(string $statusCode)
  {
    $code = isset(self::$descriptions[$statusCode]) ? $statusCode : 0;

    $this->code = $code;
    $this->description = self::$descriptions[$code];
  }
}