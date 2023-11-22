<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\Smtp2go;
use Utopia\Messaging\Messages\Email;

class Smtp2goTest extends Base
{
    public function testSendEmailWithSmtp2go()
    {
        // Retrieve credentials from environment variables
        $smtp2goUsername = getenv('SMTP2GO_USERNAME');
        $smtp2goPassword = getenv('SMTP2GO_PASSWORD');
        $smtp2goServer = getenv('SMTP2GO_SERVER');
        $smtp2goPort = getenv('SMTP2GO_PORT');

        // Ensure all required environment variables are set
        $this->assertNotEmpty($smtp2goUsername, 'SMTP2GO_USERNAME is not set');
        $this->assertNotEmpty($smtp2goPassword, 'SMTP2GO_PASSWORD is not set');
        $this->assertNotEmpty($smtp2goServer, 'SMTP2GO_SERVER is not set');
        $this->assertNotEmpty($smtp2goPort, 'SMTP2GO_PORT is not set');

        // Instantiate the SMTP2GO adapter
        $sender = new Smtp2go($smtp2goServer, $smtp2goPort, $smtp2goUsername, $smtp2goPassword);

        // Email details
        $to = 'Receivers-email';
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = 'Senders-email';

        // Create an email message
        $message = new Email(
            to: [$to],
            subject: $subject,
            content: $content,
            from: $from
        );

        // Send the email using SMTP2GO
        $result = $sender->send($message);

        // Check if the email was sent successfully
        $this->assertEquals('Email sent successfully', $result);
    }
}
