<?php

namespace Utopia\Messaging\Adapters\Email;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Adapters\Email as EmailAdapter;

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
     * @return string
     * @throws Exception
     * @throws \Exception
     */
    protected function process(Email $message): string
    {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->XMailer = 'Utopia Mailer';
        $mail->Host = 'maildev';
        $mail->Port = 1025;
        $mail->SMTPAuth = false;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = 'false';
        $mail->SMTPAutoTLS = false;
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $message->getSubject();
        $mail->Body = $message->getContent();
        $mail->AltBody = \strip_tags($message->getContent());
        $mail->setFrom($this->$message->getFrom(), 'Utopia');
        $mail->addReplyTo($this->$message->getFrom(), 'Utopia');
        $mail->isHTML($message->isHtml());

        foreach ($message->getTo() as $to) {
            $mail->addAddress($to);
        }

        if (!$mail->send()) {
            throw new \Exception($mail->ErrorInfo);
        }

        return 'true';
    }
}
