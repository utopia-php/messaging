<?php

namespace Utopia\Messaging;

class Messenger
{
    private array $adapters;

    public function __construct(Adapter|array $adapters)
    {
        if ($adapters instanceof Adapter) {
            $adapters = [$adapters];
        }

        if (empty($adapters)) {
            throw new \InvalidArgumentException('At least one adapter must be provided.');
        }

        foreach ($adapters as $index => $adapter) {
            if (! $adapter instanceof Adapter) {
                throw new \InvalidArgumentException(
                    'All elements must be instances of Adapter, but element '
                    .$index
                    .' is '
                    .\get_debug_type($adapter)
                    .'.'
                );
            }
        }

        $this->validateAdapters($adapters);

        $this->adapters = $adapters;
    }

    public function send(Message $message): array
    {
        $errors = [];
        $messageType = $this->adapters[0]->getMessageType();

        if (! \is_a($message, $messageType)) {
            throw new \Exception(
                'Invalid message type. Expected "'
                .$messageType
                .'", got "'
                .\get_class($message)
                .'".'
            );
        }

        foreach ($this->adapters as $index => $adapter) {
            try {
                return $adapter->send($message);
            } catch (\Exception $e) {
                $errors[] = $adapter->getName()
                    .' (adapter '
                    .($index + 1)
                    .'): '
                    .$e->getMessage();
            }
        }

        $adapterCount = \count($this->adapters);
        $adapterLabel = $adapterCount === 1 ? 'adapter' : 'adapters';

        throw new \Exception(
            'All '
            .$adapterCount
            .' '
            .$adapterLabel
            ." failed:\n"
            .\implode("\n", $errors)
        );
    }

    public function getMessageType(): string
    {
        return $this->adapters[0]->getMessageType();
    }

    public function getType(): string
    {
        return $this->adapters[0]->getType();
    }

    public function getMaxMessagesPerRequest(): int
    {
        return array_reduce(
            $this->adapters,
            fn ($min, $adapter) => min($min, $adapter->getMaxMessagesPerRequest()),
            PHP_INT_MAX
        );
    }

    private function validateAdapters(array $adapters): void
    {
        $firstAdapter = $adapters[0];
        $expectedType = $firstAdapter->getType();
        $expectedMessageType = $firstAdapter->getMessageType();

        foreach (\array_slice($adapters, 1, preserve_keys: true) as $index => $adapter) {
            if ($adapter->getType() !== $expectedType) {
                throw new \InvalidArgumentException(
                    'All adapters must be of the same type. Expected "'
                    .$expectedType
                    .'", but adapter '
                    .($index + 1)
                    .' ('
                    .$adapter->getName()
                    .') has type "'
                    .$adapter->getType()
                    .'".'
                );
            }

            if ($adapter->getMessageType() !== $expectedMessageType) {
                throw new \InvalidArgumentException(
                    'All adapters must support the same message type. Expected "'
                    .$expectedMessageType
                    .'", but adapter '
                    .($index + 1)
                    .' ('
                    .$adapter->getName()
                    .') supports "'
                    .$adapter->getMessageType()
                    .'".'
                );
            }
        }
    }
}
