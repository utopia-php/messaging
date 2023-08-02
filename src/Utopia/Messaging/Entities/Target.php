<?php

namespace Utopia\Messaging\Adapters\Push\Entities;

use Utopia\Messaging\Adapters\Push\Entities\Entity;

class Target extends Entity {

  public string $userId;
  public string $userInternalId;
  public string $providerId;
  public string $providerInternalId;
  public string $providerType;
  public string $identifier;

  public function __construct(
    string $id,
    string $userId,
    string $userInternalId,
    string $providerId,
    string $providerInternalId,
    string $providerType,
    string $identifier
  ) {
    $this->id = $id;
    $this->userId = $userId;
    $this->userInternalId = $userInternalId;
    $this->providerId = $providerId;
    $this->providerInternalId = $providerInternalId;
    $this->providerType = $providerType;
    $this->identifier = $identifier;
  }

}