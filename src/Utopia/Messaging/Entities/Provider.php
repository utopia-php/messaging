<?php

namespace Utopia\Messaging\Adapters\Push\Entities;

use Utopia\Messaging\Adapters\Push\Entities\Entity;

class Provider extends Entity {

  public string $userId = '';

  public string $name = '';

  public string $provider = '';

  public string $type = '';

  public array $credentials = [];

  public function __construct(
    string $id,
    string $userId,
    string $name,
    string $provider,
    string $type,
    array $credentials
  ) {
    $this->id = $id;
    $this->userId = $userId;
    $this->name = $name;
    $this->provider = $provider;
    $this->type = $type;
    $this->credentials = $credentials;
  }

}