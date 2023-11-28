<?php

namespace Utopia\Messaging;

class Response
{
    private int $deliveredTo;

    private string $type;

    /**
     * @var array<string, string>
     */
    private array $details;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->deliveredTo = 0;
        $this->details = [];
    }

    public function setDeliveredTo(int $deliveredTo): void
    {
        $this->deliveredTo = $deliveredTo;
    }

    public function incrementDeliveredTo(): void
    {
        $this->deliveredTo++;
    }

    public function getDeliveredTo(): int
    {
        return $this->deliveredTo;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function addToDetails(string $recipient, string $error = ''): void
    {
        $this->details[] = [
            'recipient' => $recipient,
            'status' => $error === '' ? 'success' : 'failure',
            'error' => $error,
        ];
    }

    public function popFromDetails(): void
    {
        array_pop($this->details);
    }

    /**
     * @return array<string, string>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'deliveredTo' => $this->deliveredTo,
            'type' => $this->type,
            'details' => $this->details,
        ];
    }
}
