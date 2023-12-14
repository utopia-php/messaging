<?php

namespace Utopia\Messaging;

class Response
{
    private int $deliveredTo;

    private string $type;

    /**
     * @var array<array<string, string>>
     */
    private array $results;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->deliveredTo = 0;
        $this->results = [];
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

    public function addResultForRecipient(string $recipient, string $error = ''): void
    {
        $this->results[] = [
            'recipient' => $recipient,
            'status' => empty($error) ? 'success' : 'failure',
            'error' => $error,
        ];
    }

    public function popFromResults(): void
    {
        array_pop($this->results);
    }

    /**
     * @return array<array<string, string>>
     */
    public function getDetails(): array
    {
        return $this->results;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'deliveredTo' => $this->deliveredTo,
            'type' => $this->type,
            'results' => $this->results,
        ];
    }

    /**
     * @param  array<array<string, string>>  $results
     */
    public function fromArray(array $results): self
    {
        $response = new self($this->type);
        $response->deliveredTo = $this->deliveredTo;
        $response->results = $this->results;

        return $response;
    }
}
