<?php

namespace Utopia\Messaging;

class Response
{
    private int $success;
    private int $failure;
    private string $type;
    private array $details;

    public function __construct(int $success, int $failure, string $type, array $details)
    {
        $this->success = $success;
        $this->failure = $failure;
        $this->type = $type;
        $this->details = $details;
    }

    public function setSuccess(int $success): void
    {
        $this->success = $success;
    }

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function setFailure(int $failure): void
    {
        $this->failure = $failure;
    }

    public function getFailure(): int
    {
        return $this->failure;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setDetails(array $details): void
    {
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'failure' => $this->failure,
            'type' => $this->type,
            'details' => $this->details,
        ];
    }
}