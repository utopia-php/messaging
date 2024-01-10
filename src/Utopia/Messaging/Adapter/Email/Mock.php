<?php

namespace Utopia\Messaging\Adapter\Email;

use PHPMailer\PHPMailer\PHPMailer;
use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;

class Mock extends EmailAdapter
{
    protected const NAME = 'Mock';

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
        $mail->Host = 'maildev';
        $mail->Port = 1025;
        $mail->SMTPAuth = false;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $message->getSubject();
        $mail->Body = $message->getContent();
        $mail->AltBody = \strip_tags($message->getContent());
        $mail->setFrom($message->getFromEmail(), $message->getFromName());
        $mail->addReplyTo($message->getReplyToEmail(), $message->getReplyToName());
        $mail->isHTML($message->isHtml());

        foreach ($message->getTo() as $to) {
            $mail->addAddress($to);
        }

        if (!$mail->send()) {
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to, $mail->ErrorInfo);
            }
        } else {
            $response->setDeliveredTo(\count($message->getTo()));
            foreach ($message->getTo() as $to) {
                $response->addResultForRecipient($to);
            }
        }

        return $response->toArray();
    }
}
