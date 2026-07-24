<?php

namespace Utopia\Messaging\Adapters\Email;

use Utopia\Messaging\Adapters\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email;

class MailSlurp extends EmailAdapter
{
    /**
     * @param  string $apiKey Your MailSlurp API key to authenticate with the API.
     * @param  ?string $inboxId Your MailSlurp inbox ID to send emails from. If left empty, a random inbox will be used.
     */
    public function __construct(
        private string $apiKey,
        private ?string $inboxId = null,
    ) {
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'MailSlurp';
    }

    /**
     * Get adapter description.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int
    {
		// TODO: Find out the actual limit. The docs say "limit varies from plan to plan" without exact numbers.
        return 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(Email $message): string
    {
		$url = "https://api.mailslurp.com/emails";
		if ($this->inboxId !== null) {
			$url .= '?inboxId='.$this->inboxId;
		}
		return $this->request(
			method: 'POST',
			url: $url,
			headers: [
				'x-api-key: '.$this->apiKey,
				'Content-Type: application/json',
			],
			body: \json_encode([
				'to' => $message->getTo(),
				'from' => $message->getFrom(),
				'subject' => $message->getSubject(),
				'text' => $message->getContent(),
				'html' => $message->isHtml(),
			]),
		);
    }
}
