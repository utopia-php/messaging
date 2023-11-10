<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Adapter\SMS\GEOSMS\CallingCode;
use Utopia\Messaging\Messages\SMS;

class GEOSMS extends SMSAdapter
{
    protected SMSAdapter $defaultAdapter;

    /**
     * @var array<string, SMSAdapter>
     */
    protected array $localAdapters = [];

    public function __construct(SMSAdapter $defaultAdapter)
    {
        $this->defaultAdapter = $defaultAdapter;
    }

    public function getName(): string
    {
        return 'GEOSMS';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return PHP_INT_MAX;
    }

    public function setLocal(string $callingCode, SMSAdapter $adapter): self
    {
        $this->localAdapters[$callingCode] = $adapter;

        return $this;
    }

    /**
     * @param SMSAdapter $adapter
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

    protected function process(SMS $message): string
    {
        $results = [];
        $recipients = $message->getTo();

        do {
            [$nextRecipients, $nextAdapter] = $this->getNextRecipientsAndAdapter($recipients);

            try {
                $results[$nextAdapter->getName()] = json_decode($nextAdapter->send(
                    new SMS(
                        to: $nextRecipients,
                        content: $message->getContent(),
                        from: $message->getFrom(),
                        attachments: $message->getAttachments()
                    )
                ));
            } catch (\Exception $e) {
                $results[$nextAdapter->getName()] = [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ];
            }

            $recipients = \array_diff($recipients, $nextRecipients);
        } while (count($recipients) > 0);

        return \json_encode($results);
    }

    /**
     * @param array<string> $recipients
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
