<?php

namespace Utopia\Push\Gcm;

class Message
{
  /**
   * @var array
   */
  private $registrationIds = [];

  /**
   * @var associate array
   */
  private $data;

  /**
   * Construct.
   * 
   * @param array $registrationIds
   * @param mixed $data
   */
  public function __construct(array $registrationIds, mixed $data)
  {
    $this->registrationIds = $registrationIds;
    $this->data = is_string($data) ? ['message' => $data] : $data;
  }

  /**
   * Adds a device registration id to list.
   */
  public function addRegistrationId($registrationId)
  {
    $this->registrationIds[] = $registrationId;
  }

  public function toJson()
  {
    return json_encode([
      'registration_ids' => $this->registrationIds,
      'data' => $this->data
    ], 
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
  }
}