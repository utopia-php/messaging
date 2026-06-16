<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Adapter\SMS\GEOSMS\CallingCode;
use Utopia\Messaging\Messages\SMS;
use Utopia\Telemetry\Adapter as Telemetry;
use Utopia\Telemetry\Adapter\None as NoTelemetry;

class GEOSMS extends SMSAdapter
{
    protected const NAME = 'GEOSMS';

    protected SMSAdapter $defaultAdapter;

    private Telemetry $telemetry;

    /**
     * @var array<string, SMSAdapter>
     */
    protected array $localAdapters = [];

    public function __construct(SMSAdapter $defaultAdapter)
    {
        $this->telemetry = new NoTelemetry();
        parent::__construct($this->telemetry);
        $this->defaultAdapter = $defaultAdapter;
        $this->defaultAdapter->setTelemetry($this->telemetry);
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return PHP_INT_MAX;
    }

    public function setLocal(string $callingCode, SMSAdapter $adapter): self
    {
        $this->localAdapters[$callingCode] = $adapter;
        $adapter->setTelemetry($this->telemetry);

        return $this;
    }

    public function setTelemetry(Telemetry $telemetry): void
    {
        $this->telemetry = $telemetry;
        parent::setTelemetry($telemetry);

        $this->defaultAdapter->setTelemetry($telemetry);

        foreach ($this->localAdapters as $adapter) {
            $adapter->setTelemetry($telemetry);
        }
    }

    /**
     * @return array<string>
     */
    protected function filterCallingCodesByAdapter(SMSAdapter $adapter): array
    {
        $result = [];

        foreach ($this->localAdapters as $callingCode => $localAdapter) {
            if ($localAdapter === $adapter) {
                $result[] = $callingCode;
            }
        }

        return $result;
    }

    /**
     * @return array<string, array{deliveredTo: int, type: string, results: array<array<string, mixed>>}>
     */
    protected function process(SMS $message): array
    {
        $results = [];
        $recipients = $message->getTo();

        do {
            [$nextRecipients, $nextAdapter] = $this->getNextRecipientsAndAdapter($recipients);

            try {
                $results[$nextAdapter->getName()] = $nextAdapter->send(
                    (new SMS(
                        to: $nextRecipients,
                        content: $message->getContent(),
                        from: $message->getFrom(),
                        attachments: $message->getAttachments()
                    ))->setOrigin($message->getOrigin())
                );
            } catch (\Exception $e) {
                $results[$nextAdapter->getName()] = [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ];
            }

            $recipients = \array_diff($recipients, $nextRecipients);
        } while (count($recipients) > 0);

        return $results;
    }

    /**
     * @param  array<string>  $recipients
     * @return array<array<string>|SMSAdapter>
     */
    protected function getNextRecipientsAndAdapter(array $recipients): array
    {
        $nextRecipients = [];
        $nextAdapter = null;

        foreach ($recipients as $recipient) {
            $adapter = $this->getAdapterByPhoneNumber($recipient);

            if ($nextAdapter === null || $adapter === $nextAdapter) {
                $nextAdapter = $adapter;
                $nextRecipients[] = $recipient;
            }
        }

        return [$nextRecipients, $nextAdapter];
    }

    protected function getAdapterByPhoneNumber(?string $phoneNumber): SMSAdapter
    {
        $callingCode = CallingCode::fromPhoneNumber($phoneNumber);
        if (empty($callingCode)) {
            return $this->defaultAdapter;
        }

        if (isset($this->localAdapters[$callingCode])) {
            return $this->localAdapters[$callingCode];
        }

        return $this->defaultAdapter;
    }
}
