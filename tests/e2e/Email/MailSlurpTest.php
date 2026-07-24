<?php

namespace Tests\E2E;

use Utopia\Messaging\Adapters\Email\MailSlurp;
use Utopia\Messaging\Messages\Email;

class MailSlurpTest extends Base
{
    /**
     * @throws \Exception
     */
    public function testSendEmail()
    {
        $key = getenv('MAILSLURP_API_KEY');
		$inboxId = getenv('MAILSLURP_INBOX_ID');
        $sender = new MailSlurp($key, empty($inboxId) ? null : $inboxId);

        $to = getenv('TEST_EMAIL');
        $subject = 'Test Subject';
        $content = 'Test Content';
        $from = getenv('TEST_FROM_EMAIL');

        $message = new Email(
            to: [$to],
			subject: $subject,
			content: $content,
			from: $from,
        );

        $response = $sender->send($message);
        $this->assertEquals($response, '');
    }
}
