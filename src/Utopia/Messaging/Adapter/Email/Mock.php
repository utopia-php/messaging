<?php

namespace Utopia\Messaging\Adapter\Email;

use PHPMailer\PHPMailer\PHPMailer;
use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;

class Mock extends EmailAdapter
{
    protected const NAME = 'Mock';

    public function __construct(
        private readonly string $host = 'maildev',
        private readonly int $port = 1025,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        // TODO: Find real value for this
        return 1000;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(EmailMessage $message): array
    {
        $response = new Response($this->getType());
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->XMailer = 'Utopia Mailer';
        $mail->Host = $this->host;
        $mail->Port = $this->port;
        $mail->SMTPAuth = false;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $message->getSubject();
        $mail->Body = $message->getContent();
        $mail->AltBody = strip_tags($message->getContent());
        $mail->setFrom($message->getFromEmail(), $message->getFromName());
        $mail->addReplyTo($message->getReplyToEmail(), $message->getReplyToName());
        $mail->isHTML($message->isHtml());

        foreach ($message->getTo() as $to) {
            $mail->addAddress($to['email'], $to['name'] ?? '');
        }

        if (!\in_array($message->getCC(), [null, []], true)) {
            foreach ($message->getCC() as $cc) {
                $mail->addCC($cc['email'], $cc['name'] ?? '');
            }
        }

        if (!\in_array($message->getBCC(), [null, []], true)) {
            foreach ($message->getBCC() as $bcc) {
                $mail->addBCC($bcc['email'], $bcc['name'] ?? '');
            }
        }

        if (!$mail->send()) {
            foreach ($message->getTo() as $to) {
                $response->addResult($to['email'], $mail->ErrorInfo);
            }
        } else {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResult($to['email']);
            }
        }

        return $response->toArray();
    }
}
