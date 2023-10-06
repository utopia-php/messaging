<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Messages\Email;
use Utopia\Messaging\Adapters\Email as EmailAdapter;
use PHPMailer\PHPMailer\PHPMailer;

class Smtp2go extends EmailAdapter
{
    private string $smtpServer;
    private string $smtpUsername;
    private string $smtpPassword;
    private int $smtpPort;

    public function __construct() 
    {
        // Retrieve SMTP server details from environment variables
        $this->smtpServer = getenv('SMTP2GO_SERVER');
        $this->smtpUsername = getenv('SMTP2GO_USERNAME');
        $this->smtpPassword = getenv('SMTP2GO_PASSWORD');
        $this->smtpPort = getenv('SMTP2GO_PORT');
        
    }

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
        // Create a PHPMailer instance
        $mailer = new PHPMailer();

        // Configure SMTP settings
        $mailer->isSMTP();
        $mailer->Host = $this->smtpServer;
        $mailer->SMTPAuth = true;
        $mailer->Username = $this->smtpUsername;
        $mailer->Password = $this->smtpPassword;
        $mailer->Port = $this->smtpPort;

        // Set email content
        $mailer->setFrom('ayaanbordoloi25@devfun.cloud', 'Ayaan');
        $mailer->addAddress('ayaansive25@gmail.com', 'Rohan');
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
