<?php

namespace Utopia\Messaging\Adapter\Email;

use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;

class Resend extends EmailAdapter
{
    protected const NAME = 'Resend';

    protected const MAX_ATTACHMENT_BYTES = 40 * 1024 * 1024;

    /**
     * @param  string  $apiKey  Your Resend API key to authenticate with the API.
     */
    public function __construct(
        private string $apiKey
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 100;
    }

    /**
     * @link https://resend.com/docs/api-reference/emails/send-batch-emails
     * @link https://resend.com/docs/api-reference/emails/send-email
     */
    protected function process(EmailMessage $message): array
    {
        $attachments = [];
        if (! \is_null($message->getAttachments()) && ! empty($message->getAttachments())) {
            $size = 0;
            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getContent() !== null) {
                    $size += \strlen($attachment->getContent());
                } else {
                    $fileSize = \filesize($attachment->getPath());
                    if ($fileSize === false) {
                        throw new \Exception('Failed to read attachment file: '.$attachment->getPath());
                    }
                    $size += $fileSize;
                }
            }

            if ($size > self::MAX_ATTACHMENT_BYTES) {
                throw new \Exception('Total attachment size exceeds '.self::MAX_ATTACHMENT_BYTES.' bytes');
            }

            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getContent() !== null) {
                    $content = \base64_encode($attachment->getContent());
                } else {
                    $data = \file_get_contents($attachment->getPath());
                    if ($data === false) {
                        throw new \Exception('Failed to read attachment file: '.$attachment->getPath());
                    }
                    $content = \base64_encode($data);
                }

                $attachments[] = [
                    'filename' => $attachment->getName(),
                    'content' => $content,
                    'content_type' => $attachment->getType(),
                ];
            }
        }

        $response = new Response($this->getType());

        $emails = [];
        foreach ($message->getTo() as $to) {
            $toFormatted = ! empty($to['name'])
                ? "{$to['name']} <{$to['email']}>"
                : $to['email'];

            $email = [
                'from' => $message->getFromName()
                    ? "{$message->getFromName()} <{$message->getFromEmail()}>"
                    : $message->getFromEmail(),
                'to' => [$toFormatted],
                'subject' => $message->getSubject(),
            ];

            if ($message->isHtml()) {
                $email['html'] = $message->getContent();
            } else {
                $email['text'] = $message->getContent();
            }

            if (! empty($message->getReplyToEmail())) {
                $email['reply_to'] = $message->getReplyToName()
                    ? ["{$message->getReplyToName()} <{$message->getReplyToEmail()}>"]
                    : [$message->getReplyToEmail()];
            }

            if (! \is_null($message->getCC()) && ! empty($message->getCC())) {
                $ccList = \array_map(
                    fn ($cc) => ! empty($cc['name'])
                        ? "{$cc['name']} <{$cc['email']}>"
                        : $cc['email'],
                    $message->getCC()
                );
                $email['cc'] = $ccList;
            }

            if (! empty($attachments)) {
                $email['attachments'] = $attachments;
            }

            if (! \is_null($message->getBCC()) && ! empty($message->getBCC())) {
                $bccList = \array_map(
                    fn ($bcc) => ! empty($bcc['name'])
                        ? "{$bcc['name']} <{$bcc['email']}>"
                        : $bcc['email'],
                    $message->getBCC()
                );
                $email['bcc'] = $bccList;
            }

            $emails[] = $email;
        }

        $headers = [
            'Authorization: Bearer '.$this->apiKey,
            'Content-Type: application/json',
        ];

        if (! empty($attachments)) {
            return $this->sendIndividually($message, $emails, $headers, $response);
        }

        return $this->sendBatch($message, $emails, $headers, $response);
    }

    /**
     * @param  array<array<string, mixed>>  $emails
     * @param  array<string>  $headers
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    private function sendBatch(EmailMessage $message, array $emails, array $headers, Response $response): array
    {
        $result = $this->request(
            method: 'POST',
            url: 'https://api.resend.com/emails/batch',
            headers: $headers,
            body: $emails,
        );

        $statusCode = $result['statusCode'];

        if ($statusCode === 200) {
            $responseData = $result['response'];

            if (\is_array($responseData) && isset($responseData['errors']) && ! empty($responseData['errors'])) {
                $failedIndices = [];
                foreach ($responseData['errors'] as $error) {
                    $failedIndices[$error['index']] = $error['message'];
                }

                foreach ($message->getTo() as $index => $to) {
                    if (isset($failedIndices[$index])) {
                        $response->addResult($to['email'], $failedIndices[$index]);
                    } else {
                        $response->addResult($to['email']);
                    }
                }

                $successCount = \count($message->getTo()) - \count($failedIndices);
                $response->setDeliveredTo($successCount);
            } else {
                $response->setDeliveredTo(\count($message->getTo()));
                foreach ($message->getTo() as $to) {
                    $response->addResult($to['email']);
                }
            }
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $errorMessage = $this->extractErrorMessage($result['response'], 'Unknown error');

            foreach ($message->getTo() as $to) {
                $response->addResult($to['email'], $errorMessage);
            }
        } elseif ($statusCode >= 500) {
            $errorMessage = $this->extractErrorMessage($result['response'], 'Server error');

            foreach ($message->getTo() as $to) {
                $response->addResult($to['email'], $errorMessage);
            }
        }

        return $response->toArray();
    }

    /**
     * @param  array<array<string, mixed>>  $emails
     * @param  array<string>  $headers
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     */
    private function sendIndividually(EmailMessage $message, array $emails, array $headers, Response $response): array
    {
        $recipients = $message->getTo();
        $deliveredTo = 0;

        foreach ($emails as $index => $email) {
            $to = $recipients[$index];

            $result = $this->request(
                method: 'POST',
                url: 'https://api.resend.com/emails',
                headers: $headers,
                body: $email,
            );

            $statusCode = $result['statusCode'];

            if ($statusCode >= 200 && $statusCode < 300) {
                $response->addResult($to['email']);
                $deliveredTo++;
            } elseif ($statusCode >= 400 && $statusCode < 500) {
                $errorMessage = $this->extractErrorMessage($result['response'], 'Unknown error');
                $response->addResult($to['email'], $errorMessage);
            } else {
                $errorMessage = $this->extractErrorMessage($result['response'], 'Server error');
                $response->addResult($to['email'], $errorMessage);
            }
        }

        $response->setDeliveredTo($deliveredTo);

        return $response->toArray();
    }

    /**
     * @param  array<string, mixed>|string|null  $body
     */
    private function extractErrorMessage(array|string|null $body, string $default): string
    {
        if (\is_string($body)) {
            return $body;
        }

        if (\is_array($body)) {
            if (isset($body['message']) && \is_string($body['message'])) {
                return $body['message'];
            }

            if (isset($body['error']) && \is_string($body['error'])) {
                return $body['error'];
            }
        }

        return $default;
    }
}
