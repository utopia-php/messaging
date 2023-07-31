<?php

namespace Utopia\Messaging\Adapters\Push\Entities;

use DateTime;
use Utopia\Messaging\Adapters\Push\Entities\Entity;

class Subscriber extends Entity {

  public string $userId;
  public string $userInternalId;
  public string $targetId;
  public string $targetInternalId;
  public string $topicId;
  public string $topicInternalId;

  public function __construct(
    string $id,
    string $userId,
    string $userInternalId,
    string $targetId,
    string $targetInternalId,
  ) {
    $this->id = $id;
    $this->userId = $userId;
    $this->userInternalId = $userInternalId;
    $this->targetId = $targetId;
    $this->targetInternalId = $targetInternalId;
  }

}