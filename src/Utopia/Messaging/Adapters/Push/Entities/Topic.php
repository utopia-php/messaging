<?php

namespace Utopia\Messaging\Adapters\Push\Entities;

use DateTime;
use Utopia\Messaging\Adapters\Push\Entities\Entity;

class Topic extends Entity {

  public string $providerId = '';
  public string $providerInternalId = '';
  public string $name;
  public string $description;

  public function __construct(
    string $id,
    string $providerId,
    string $providerInternalId,
    string $name,
    string $description
  ) {
    $this->id = $id;
    $this->providerId = $providerId;
    $this->providerInternalId = $providerInternalId;
    $this->name = $name;
    $this->description = $description;
  }

}