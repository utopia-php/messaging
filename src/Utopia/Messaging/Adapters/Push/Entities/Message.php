<?php

namespace Utopia\Messaging\Adapters\Push\Entities;

use DateTime;
use Utopia\Messaging\Adapters\Push\Entities\Entity;

class Message extends Entity {
  public string $providerId = '';
  public string $providerInternalId = '';
  public ?array $data;
  public array $to = [];
  public ?DateTime $deliveryTime;
  public ?string $deliveryError;
  public int $deliveredTo;
  public bool $delivered;
  public string $search;

  public function __construct(
    string $id,
    string $providerId,
    string $providerInternalId,
    ?array $data,
    array $to,
    ?DateTime $deliveryTime,
    ?string $deliveryError,
    int $deliveredTo,
    bool $delivered,
    string $search
  ) {
    $this->id = $id;
    $this->providerId = $providerId;
    $this->providerInternalId = $providerInternalId;
    $this->data = $data;
    $this->to = $to;
    $this->deliveryTime = $deliveryTime;
    $this->deliveryError = $deliveryError;
    $this->deliveredTo = $deliveredTo;
    $this->delivered = $delivered;
    $this->search = $search;
  }

}