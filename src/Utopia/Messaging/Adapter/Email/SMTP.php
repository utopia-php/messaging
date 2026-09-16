<?php

namespace Utopia\Messaging\Adapter\Email;

use Swoole\Coroutine;
use Utopia\Messaging\Adapter\Email as EmailAdapter;
use Utopia\Messaging\Messages\Email as EmailMessage;
use Utopia\Messaging\Response;
use Utopia\SMTP\Auth\Login;
use Utopia\SMTP\Auth\Plain;
use Utopia\SMTP\Client;
use Utopia\SMTP\Encryption;
use Utopia\SMTP\Exception\SmtpException;
use Utopia\SMTP\Exception\TransactionException;
use Utopia\SMTP\Message as SmtpMessage;
use Utopia\SMTP\Timeouts;
use Utopia\SMTP\Transport\Native;

class SMTP extends EmailAdapter
{
    protected const NAME = 'SMTP';

    /**
     * The session held between sends outside a coroutine. Inside one it
     * lives in the coroutine's context instead, so concurrent requests in a
     * Swoole worker never read each other's replies off one socket.
     */
    private ?Client $client = null;

    /**
     * @param string $host SMTP hosts. Either a single hostname or multiple semicolon-delimited hostnames. A port may follow a hostname after a colon (e.g. "smtp1.example.com:25;smtp2.example.com"), and an address literal is bracketed to keep its own colons apart from it (e.g. "[::1]:587"). An encryption prefix may lead each entry (e.g. "tls://smtp1.example.com:587;ssl://smtp2.example.com:465"). Hosts are tried in order.
     * @param int $port The default SMTP server port.
     * @param string $username Authentication username.
     * @param string $password Authentication password.
     * @param string $smtpSecure SMTP Secure prefix. Can be '', 'ssl' or 'tls'
     * @param bool $smtpAutoTLS Enable/disable SMTP AutoTLS feature. Defaults to false.
     * @param string $xMailer The value to use for the X-Mailer header.
     * @param int $timeout SMTP timeout in seconds.
     * @param bool $keepAlive Whether to reuse the SMTP connection across process() calls.
     * @param int $timelimit SMTP command timelimit in seconds.
     * @param int $pingThreshold Seconds a kept session may sit idle before it is probed ahead of the next message and replaced if the server has closed it in the meantime. Keep this well above a few seconds: a server may drop a session that sends too many commands carrying no mail.
     * @param int $restartThreshold Messages a kept session carries before it is closed and a fresh one opened. 0 keeps the session for as long as the server does.
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
        private readonly int $pingThreshold = 100,
        private readonly int $restartThreshold = 100,
    ) {
        parent::__construct();
        if (!\in_array($this->smtpSecure, ['', 'ssl', 'tls'])) {
            throw new \InvalidArgumentException('Invalid SMTP secure prefix. Must be "", "ssl" or "tls"');
        }
    }

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
        $recipients = $this->recipients($message);

        try {
            $client = $this->client();
        } catch (SmtpException $exception) {
            foreach ($recipients as $email) {
                $response->addResult($email, $exception->getMessage());
            }

            return $response->toArray();
        }

        try {
            $result = $client->send($this->build($message));

            // A server may refuse some recipients and accept others, and the
            // message still reaches the rest. Each address gets its own answer
            // rather than every address getting the same one.
            $response->setDeliveredTo(\count($result->accepted));

            foreach ($result->accepted as $email) {
                $response->addResult($email);
            }

            foreach ($result->rejected as $email => $reply) {
                $response->addResult($email, (string) $reply);
            }
        } catch (TransactionException $exception) {
            // A refusal is an answer and the session goes on. A 421 is the
            // server hanging up, and the client has already dropped it.
            foreach ($recipients as $email) {
                $response->addResult($email, (string) $exception->reply);
            }
        } catch (SmtpException $exception) {
            foreach ($recipients as $email) {
                $response->addResult($email, $exception->getMessage());
            }

            // The client has dropped a stream it cannot trust; let the next
            // send start from a host choice rather than this one.
            $this->disconnect();
        } finally {
            if (!$this->keepAlive) {
                $client->close();
                $this->hold(null);
            }
        }

        return $response->toArray();
    }

    /**
     * Close a connection held open between sends. Doing nothing is safe.
     */
    public function disconnect(): void
    {
        $this->held()?->close();
        $this->hold(null);
    }

    /**
     * The first host that answers, tried in the order they were given.
     */
    private function client(): Client
    {
        $held = $this->held();

        if ($held instanceof Client) {
            if ($this->reusable($held)) {
                return $held;
            }

            $held->close();
            $this->hold(null);
        }

        $timeouts = new Timeouts(
            connect: (float) $this->timeout,
            read: (float) $this->timelimit,
            write: (float) $this->timelimit,
        );

        $failures = [];

        foreach ($this->hosts() as [$host, $port, $encryption]) {
            $client = new Client(
                new Native($host, $port),
                gethostname() ?: 'localhost',
                $this->authenticators(),
                $encryption,
                $timeouts,
            );

            try {
                // Nothing is dialled until a session is needed, so asking what
                // the server offers is what settles whether this host answers.
                $client->capabilities();
            } catch (SmtpException $exception) {
                $failures[] = "{$host}:{$port} ({$exception->getMessage()})";

                continue;
            }

            if ($this->keepAlive) {
                $this->hold($client);
            }

            return $client;
        }

        throw new \Utopia\SMTP\Exception\ConnectionException(
            'No SMTP host answered: ' . implode('; ', $failures),
        );
    }

    /**
     * Whether a held session is worth handing another message. A long-lived
     * one is rotated the way a relay expects, and an idle one is asked
     * whether it is still there before it is trusted with MAIL FROM.
     */
    private function reusable(Client $client): bool
    {
        if ($this->restartThreshold > 0 && $client->transactions() >= $this->restartThreshold) {
            return false;
        }

        return $client->idle() <= $this->pingThreshold || $client->ping();
    }

    private function held(): ?Client
    {
        if ($this->coroutine()) {
            $client = Coroutine::getContext()[self::class] ?? null;

            return $client instanceof Client ? $client : null;
        }

        return $this->client;
    }

    private function hold(?Client $client): void
    {
        if ($this->coroutine()) {
            Coroutine::getContext()[self::class] = $client;

            return;
        }

        $this->client = $client;
    }

    private function coroutine(): bool
    {
        return \extension_loaded('swoole') && Coroutine::getCid() > 0;
    }

    /**
     * The host string, which may name several servers with their own port and
     * encryption, as a list of somewhere to try.
     *
     * @return list<array{string, int, Encryption}>
     */
    private function hosts(): array
    {
        $hosts = [];

        foreach (explode(';', $this->host) as $entry) {
            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            $encryption = $this->encryption();

            if (preg_match('#^(ssl|tls)://#i', $entry, $matches) === 1) {
                $encryption = strtolower($matches[1]) === 'ssl' ? Encryption::Implicit : Encryption::StartTls;
                $entry = substr($entry, \strlen($matches[0]));
            }

            $port = $this->port;

            // An address literal is mostly colons, so only a bracketed one or a
            // name carrying none may be followed by a port. PHPMailer's own
            // pattern reads "::1" as the host ":" on port 1.
            if (preg_match('/^(\[[^\]]+\]|[^:]+):(\d+)$/', $entry, $matches) === 1) {
                $entry = $matches[1];
                $port = (int) $matches[2];
            }

            $hosts[] = [$entry, $port, $encryption];
        }

        return $hosts === [] ? [[$this->host, $this->port, $this->encryption()]] : $hosts;
    }

    /**
     * The prefix says what to do, and without one the AutoTLS flag decides
     * whether an offered upgrade is taken.
     */
    private function encryption(): Encryption
    {
        return match ($this->smtpSecure) {
            'ssl' => Encryption::Implicit,
            'tls' => Encryption::StartTls,
            default => $this->smtpAutoTLS ? Encryption::Opportunistic : Encryption::None,
        };
    }

    /**
     * @return list<Plain|Login>
     */
    private function authenticators(): array
    {
        // The adapter this replaces treated a literal "0" as no credential,
        // an artefact of empty() that some configuration still relies on.
        if (\in_array($this->username, ['', '0'], true) || \in_array($this->password, ['', '0'], true)) {
            return [];
        }

        return [new Plain($this->username, $this->password), new Login($this->username, $this->password)];
    }

    /**
     * Every address the envelope will carry, which is what a per-recipient
     * result is keyed by.
     *
     * @return list<string>
     */
    private function recipients(EmailMessage $message): array
    {
        $recipients = [];

        foreach ([...$message->getTo(), ...($message->getCC() ?? []), ...($message->getBCC() ?? [])] as $recipient) {
            $recipients[$recipient['email']] = true;
        }

        return array_keys($recipients);
    }

    private function build(EmailMessage $message): SmtpMessage
    {
        $headers = [];

        if ($this->xMailer !== '') {
            $headers['X-Mailer'] = $this->xMailer;
        }

        if (Mime::size($message) > self::MAX_ATTACHMENT_BYTES) {
            throw new \Exception('Attachments size exceeds the maximum allowed size of 25MB');
        }

        return Mime::message(
            $message,
            $message->getTo(),
            $message->getCC() ?? [],
            $message->getBCC() ?? [],
            $headers,
        );
    }
}
