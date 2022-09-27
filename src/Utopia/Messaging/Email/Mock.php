<?php

namespace Utopia\Messaging\Email;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mock extends EmailAdapter
{
    public function getName(): string
    {
        return 'Mock';
    }

    public function getMaxMessagesPerRequest(): int
    {
        // TODO: Find real value for this
        return 1000;
    }

    /**
     * @inheritdoc
     * @throws Exception
     * @throws \Exception
     */
    protected function sendMessage(EmailMessage $message): string
    {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->XMailer = 'Utopia Mailer';
        $mail->Host = 'maildev';
        $mail->Port = 1025;
        $mail->SMTPAuth = false;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $message->getSubject();
        $mail->Body = $message->getContent();
        $mail->AltBody = \strip_tags($message->getContent());
        $mail->setFrom($message->getFrom(), 'Utopia');
        $mail->addReplyTo($message->getFrom(), 'Utopia');
        $mail->isHTML($message->isHtml());

        foreach ($message->getTo() as $to) {
            $mail->addAddress($to);
        }

        if (!$mail->send()) {
            throw new \Exception($mail->ErrorInfo);
        }

        return true;
    }
}