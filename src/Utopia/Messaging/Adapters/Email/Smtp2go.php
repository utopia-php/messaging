<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Adapters\Email as EmailAdapter;
use PHPMailer\PHPMailer\PHPMailer;

class Smtp2go extends EmailAdapter
{
    public function getName(): string
    {
        return 'SMTP2GO';
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1; // SMTP2GO typically sends one email at a time
    }

    protected function process(Email $message): string
    {
        // Retrieve SMTP server details from environment variables
        $smtpUsername = getenv('SMTP2GO_USERNAME');
        $smtpPassword = getenv('SMTP2GO_PASSWORD');
        $smtpServer = getenv('SMTP2GO_SERVER');
        $smtpPort = getenv('SMTP2GO_PORT');

        // Create a PHPMailer instance
        $mailer = new PHPMailer();

        // Configure SMTP settings
        $mailer->isSMTP();
        $mailer->Username = $smtpUsername;
        $mailer->Password = $smtpPassword;
        $mailer->Host = $smtpServer;
        $mailer->Port = $smtpPort;
        $mailer->SMTPAuth = true;

        // Set email content
        $mailer->setFrom('Senders-email', 'Name');
        $mailer->addAddress('Receivers-email', 'Name');
        $mailer->Subject = 'Test Subject';
        $mailer->Body = 'Test mail';

        // Send the email
        if ($mailer->send()) {
            return 'Email sent successfully';
        } else {
            return 'Failed to send email: ' . $mailer->ErrorInfo;
        }
    }
}
