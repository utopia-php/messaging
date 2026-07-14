<?php

namespace Utopia\Messaging\Adapter\Email;

use PHPMailer\PHPMailer\PHPMailer;
use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;

class SMTP extends EmailAdapter
{
    protected const NAME = 'SMTP';

    /**
     * @param string $host SMTP hosts. Either a single hostname or multiple semicolon-delimited hostnames. You can also specify a different port for each host by using this format: [hostname:port] (e.g. "smtp1.example.com:25;smtp2.example.com"). You can also specify encryption type, for example: (e.g. "tls://smtp1.example.com:587;ssl://smtp2.example.com:465"). Hosts will be tried in order.
     * @param int $port The default SMTP server port.
     * @param string $username Authentication username.
     * @param string $password Authentication password.
     * @param string $smtpSecure SMTP Secure prefix. Can be '', 'ssl' or 'tls'
     * @param bool $smtpAutoTLS Enable/disable SMTP AutoTLS feature. Defaults to false.
     * @param string $xMailer The value to use for the X-Mailer header.
     * @param int $timeout SMTP timeout in seconds.
     * @param bool $keepAlive Whether to reuse the SMTP connection across process() calls.
     * @param int $timelimit SMTP command timelimit in seconds.
     */
    public function __construct(
        private readonly string $host,
        private readonly int $port = 25,
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly string $smtpSecure = '',
        private readonly bool $smtpAutoTLS = false,
        private readonly string $xMailer = '',
        private readonly int $timeout = 30,
        private readonly bool $keepAlive = false,
        private readonly int $timelimit = 30,
    ) {
        parent::__construct();
        if (!\in_array($this->smtpSecure, ['', 'ssl', 'tls'])) {
            throw new \InvalidArgumentException('Invalid SMTP secure prefix. Must be "", "ssl" or "tls"');
        }
    }

    private ?PHPMailer $mail = null;

    public function getName(): string
    {
        return static::NAME;
    }

    public function getMaxMessagesPerRequest(): int
    {
        return 1000;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(EmailMessage $message): array
    {
        $response = new Response($this->getType());

        if ($this->keepAlive && $this->mail instanceof \PHPMailer\PHPMailer\PHPMailer) {
            $mail = $this->mail;
            $mail->clearAllRecipients();
            $mail->clearReplyTos();
            $mail->clearAttachments();
        } else {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->Port = $this->port;
            $mail->SMTPAuth = $this->username !== '' && $this->username !== '0' && ($this->password !== '' && $this->password !== '0');
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = $this->smtpSecure;
            $mail->SMTPAutoTLS = $this->smtpAutoTLS;
            $mail->Timeout = $this->timeout;
            $mail->SMTPKeepAlive = $this->keepAlive;

            if ($this->keepAlive) {
                $this->mail = $mail;
            }
        }

        $mail->XMailer = $this->xMailer;
        $mail->CharSet = 'UTF-8';
        $mail->getSMTPInstance()->Timelimit = $this->timelimit;
        $mail->Subject = $message->getSubject();
        $mail->Body = $message->getContent();
        $mail->setFrom($message->getFromEmail(), $message->getFromName());
        $mail->addReplyTo($message->getReplyToEmail(), $message->getReplyToName());
        $mail->isHTML($message->isHtml());

        // Strip tags misses style tags, so we use regex to remove them
        $mail->AltBody = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $mail->Body);
        $mail->AltBody = strip_tags($mail->AltBody);
        $mail->AltBody = trim($mail->AltBody);

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

        if (!\in_array($message->getAttachments(), [null, []], true)) {
            $size = 0;

            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getContent() !== null) {
                    $size += \strlen($attachment->getContent());
                } else {
                    $fileSize = filesize($attachment->getPath());
                    if ($fileSize === false) {
                        throw new \Exception('Failed to read attachment file: ' . $attachment->getPath());
                    }
                    $size += $fileSize;
                }
            }

            if ($size > self::MAX_ATTACHMENT_BYTES) {
                throw new \Exception('Attachments size exceeds the maximum allowed size of 25MB');
            }

            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getContent() !== null) {
                    $mail->addStringAttachment(
                        string: $attachment->getContent(),
                        filename: $attachment->getName(),
                        encoding: PHPMailer::ENCODING_BASE64,
                        type: $attachment->getType(),
                    );
                } else {
                    $data = file_get_contents($attachment->getPath());
                    if ($data === false) {
                        throw new \Exception('Failed to read attachment file: ' . $attachment->getPath());
                    }
                    $mail->addStringAttachment(
                        string: $data,
                        filename: $attachment->getName(),
                        encoding: PHPMailer::ENCODING_BASE64,
                        type: $attachment->getType(),
                    );
                }
            }
        }

        $sent = $mail->send();

        if ($sent) {
            $totalDelivered = \count($message->getTo()) + \count($message->getCC() ?: []) + \count($message->getBCC() ?: []);
            $response->setDeliveredTo($totalDelivered);
        }

        foreach ($message->getTo() as $to) {
            $error = empty($mail->ErrorInfo)
                ? 'Unknown error'
                : $mail->ErrorInfo;

            $response->addResult($to['email'], $sent ? '' : $error);
        }

        foreach ($message->getCC() ?? [] as $cc) {
            $error = empty($mail->ErrorInfo)
                ? 'Unknown error'
                : $mail->ErrorInfo;

            $response->addResult($cc['email'], $sent ? '' : $error);
        }

        foreach ($message->getBCC() ?? [] as $bcc) {
            $error = empty($mail->ErrorInfo)
                ? 'Unknown error'
                : $mail->ErrorInfo;

            $response->addResult($bcc['email'], $sent ? '' : $error);
        }

        return $response->toArray();
    }
}
