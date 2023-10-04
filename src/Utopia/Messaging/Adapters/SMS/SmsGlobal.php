<?php

namespace Utopia\Messaging\Adapters\SMS;

use Utopia\Messaging\Adapters\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS;

// Reference Material
// https://www.smsglobal.com/rest-api/
class SmsGlobal extends SMSAdapter
{
    /**
     * Hash Algorithm for API Authentication
     */
    const HASH_ALGO = 'sha256';

    /**
     * @param  string  $apiKey REST API key from MXT https://mxt.smsglobal.com/integrations
     * @param  string  $apiSecret REST API Secret from MXT https://mxt.smsglobal.com/integrations
     */
    public function __construct(
        private string $apiKey,
        private string $apiSecret
    ) {
    }

    public function getName(): string
    {
        return 'SmsGlobal';
    }

    public function getMaxMessagesPerRequest(): int
    {
        //TODO:: Didn't find the limit for REST API in SmsGlobal documentation
        return 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function process(SMS $message): string
    {
        $authorizationHeader = $this->getAuthorizationHeader(
            method: 'POST',
            requestUri: '/v2/sms/',
            host: 'api.smsglobal.com'
        );

        return $this->request(
            method: 'POST',
            url: "https://api.smsglobal.com/v2/sms/",
            headers: [
                'Authorization: ' . $authorizationHeader,
                'Content-Type: application/json',
            ],
            body: \json_encode(
                $this->getRequestBody(
                    to: $message->getTo(),
                    text: $message->getContent(),
                    from: $message->getFrom()
                )
            ),
        );
    }

    /**
     * Get the value to use for the Authorization header
     *
     * @param string $method HTTP method (e.g. POST)
     * @param string $requestUri Request URI (e.g. /v2/sms/)
     * @param string $host Hostname
     * @return string
     */
    public function getAuthorizationHeader(string $method, string $requestUri, string $host): string
    {
        // Server or computer time should match with the current Unix timestamp otherwise authentication will fail
        $timestamp = time();
        $nonce = md5(microtime() . mt_rand());

        $hash = $this->getRequestHash(
            timestamp: $timestamp,
            nonce: $nonce,
            method: $method,
            requestUri: $requestUri,
            host: $host
        );

        $headerFormat = 'MAC id="%s", ts="%s", nonce="%s", mac="%s"';
        $header = sprintf($headerFormat, $this->apiKey, $timestamp, $nonce, $hash);
        return $header;
    }

    /**
     * Hashes a request using the API secret, to use in the Authorization header
     *
     * @param int $timestamp Unix timestamp of request time
     * @param string $nonce Random unique string
     * @param string $method HTTP method (e.g. POST)
     * @param string $requestUri Request URI (e.g. /v1/sms/)
     * @param string $host Hostname
     * @param int $port Port (e.g. 443)
     * @return string
     */
    private function getRequestHash(
        int $timestamp,
        string $nonce,
        string $method,
        string $requestUri,
        string $host,
        int $port = 443
    ) {
        $string = array($timestamp, $nonce, $method, $requestUri, $host, $port, '');
        $string = sprintf("%s\n", implode("\n", $string));
        $hash = hash_hmac(self::HASH_ALGO, $string, $this->apiSecret, true);
        $hash = base64_encode($hash);
        return $hash;
    }

    /**
     * Get the request body
     * 
     * @param array $to
     * @param string $text
     * @param string|null $from
     * @return array
     */
    private function getRequestBody(array $to, string $text, string $from = null): array
    {
        $origin = !empty($from) ? $from : '';
        if (count($to) == 1) {
            return [
                "destination" => $to[0],
                "message" => $text,
                "origin" => $origin,
            ];
        } else {
            return [
                "destinations" => $to,
                "message" => $text,
                "origin" => $origin,
            ];
        }
    }
}
