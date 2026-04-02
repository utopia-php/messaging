<?php

namespace Utopia\Messaging;

/**
 * A messenger that orchestrates multiple adapters for failover.
 *
 * This class accepts multiple adapters and tries them in sequence.
 * If one adapter throws an exception, it will try the next one.
 * It stops at the first successful response.
 *
 * Example usage:
 * ```php
 * use Utopia\Messaging\Messenger;
 * use Utopia\Messaging\Adapter\SMS\Twilio;
 * use Utopia\Messaging\Adapter\SMS\Vonage;
 * use Utopia\Messaging\Messages\SMS;
 *
 * $messenger = new Messenger([
 *     new Twilio('sid', 'token'),
 *     new Vonage('key', 'secret'),
 * ]);
 *
 * $message = new SMS(to: ['+1234567890'], content: 'Hello!');
 * $result = $messenger->send($message);
 * ```
 */
class Messenger
{
    /**
     * @var array<Adapter>
     */
    private array $adapters;

    /**
     * @param  Adapter|array<Adapter>  $adapters  An adapter or array of adapters to try in sequence.
     *                                            At least one adapter must be provided.
     *                                            All adapters must support the same message type.
     *
     * @throws \InvalidArgumentException If no adapters are provided or adapters have mixed types.
     */
    public function __construct(Adapter|array $adapters)
    {
        if ($adapters instanceof Adapter) {
            $adapters = [$adapters];
        }

        if (empty($adapters)) {
            throw new \InvalidArgumentException('At least one adapter must be provided.');
        }

        $this->validateAdapters($adapters);

        $this->adapters = $adapters;
    }

    /**
     * Send a message using the first available adapter.
     *
     * Tries each adapter in sequence. If an adapter throws an exception,
     * it moves to the next adapter. Returns the result of the first
     * successful adapter.
     *
     * @param  Message  $message  The message to send.
     * @return array{
     *     deliveredTo: int,
     *     type: string,
     *     results: array<array<string, mixed>>
     * }
     *
     * @throws \Exception If all adapters fail or if the message type is invalid.
     */
    public function send(Message $message): array
    {
        $errors = [];
        $messageType = $this->adapters[0]->getMessageType();

        if (! \is_a($message, $messageType)) {
            throw new \Exception(sprintf(
                'Invalid message type. Expected "%s", got "%s".',
                $messageType,
                get_class($message)
            ));
        }

        foreach ($this->adapters as $index => $adapter) {
            try {
                return $adapter->send($message);
            } catch (\Exception $e) {
                $errors[] = sprintf(
                    '%s (adapter %d): %s',
                    $adapter->getName(),
                    $index + 1,
                    $e->getMessage()
                );
            }
        }

        throw new \Exception(sprintf(
            "All %d adapters failed:\n%s",
            count($this->adapters),
            implode("\n", $errors)
        ));
    }

    /**
     * Get the message type supported by this messenger.
     *
     * All adapters must support the same message type.
     */
    public function getMessageType(): string
    {
        return $this->adapters[0]->getMessageType();
    }

    /**
     * Get the adapter type (sms, email, push, etc.).
     *
     * All adapters must be of the same type.
     */
    public function getType(): string
    {
        return $this->adapters[0]->getType();
    }

    /**
     * Get the maximum number of messages that can be sent in a single request.
     *
     * Returns the minimum maxMessagesPerRequest of all adapters to ensure
     * the messenger never accepts a message that any adapter cannot handle.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return array_reduce(
            $this->adapters,
            fn ($min, $adapter) => min($min, $adapter->getMaxMessagesPerRequest()),
            PHP_INT_MAX
        );
    }

    /**
     * Validate that all adapters are compatible.
     *
     * @param  array<Adapter>  $adapters
     *
     * @throws \InvalidArgumentException If adapters are not compatible.
     */
    private function validateAdapters(array $adapters): void
    {
        $firstAdapter = $adapters[0];
        $expectedType = $firstAdapter->getType();
        $expectedMessageType = $firstAdapter->getMessageType();

        foreach ($adapters as $index => $adapter) {
            if ($adapter->getType() !== $expectedType) {
                throw new \InvalidArgumentException(sprintf(
                    'All adapters must be of the same type. Expected "%s", but adapter %d (%s) has type "%s".',
                    $expectedType,
                    $index + 1,
                    $adapter->getName(),
                    $adapter->getType()
                ));
            }

            if ($adapter->getMessageType() !== $expectedMessageType) {
                throw new \InvalidArgumentException(sprintf(
                    'All adapters must support the same message type. Expected "%s", but adapter %d (%s) supports "%s".',
                    $expectedMessageType,
                    $index + 1,
                    $adapter->getName(),
                    $adapter->getMessageType()
                ));
            }
        }
    }
}
