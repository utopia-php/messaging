<?php

namespace Utopia\Messaging\Adapter\SMS;

use Utopia\Messaging\Adapter\SMS as SMSAdapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;
use Utopia\Messaging\Response;

class AlibabaCloud extends SMSAdapter
{
    protected const NAME = 'AlibabaCloud';

    /**
     * @param string $accessKeyId Alibaba Cloud Access Key ID
     * @param string $accessKeySecret Alibaba Cloud Access Key Secret
     * @param string $signName Alibaba Cloud SMS Sign Name
     * @param string $templateCode Alibaba Cloud SMS Template Code
     */
    public function __construct(
        private string $accessKeyId,
        private string $accessKeySecret,
        private string $signName,
        private string $templateCode,
    ) {
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Get max messages per request.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(SMSMessage $message): array
    {
        $response = new Response($this->getType());

        foreach ($message->getTo() as $to) {
            $params = [
                'AccessKeyId' => $this->accessKeyId,
                'Action' => 'SendSms',
                'Format' => 'JSON',
                'PhoneNumbers' => $to,
                'RegionId' => 'cn-hangzhou',
                'SignName' => $this->signName,
                'SignatureMethod' => 'HMAC-SHA1',
                'SignatureNonce' => \uniqid(),
                'SignatureVersion' => '1.0',
                'TemplateCode' => $this->templateCode,
                'TemplateParam' => \json_encode(['code' => $message->getContent()]),
                'Timestamp' => \gmdate('Y-m-d\TH:i:s\Z'),
                'Version' => '2017-05-25',
            ];

            $params['Signature'] = $this->generateSignature($params);

            $result = $this->request(
                method: 'GET',
                url: 'https://dysmsapi.aliyuncs.com',
                headers: [],
                body: $params
            );

            if ($result['statusCode'] >= 200 && $result['statusCode'] < 300 && ($result['response']['Code'] ?? '') === 'OK') {
                $response->incrementDeliveredTo();
                $response->addResult($to);
            } else {
                $response->addResult($to, $result['response']['Message'] ?? 'Unknown error');
            }
        }

        return $response->toArray();
    }

    /**
     * Generate Alibaba Cloud API Signature.
     */
    private function generateSignature(array $params): string
    {
        \ksort($params);

        $canonicalizedQueryString = '';
        foreach ($params as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }

        $stringToSign = 'GET&' . $this->percentEncode('/') . '&' . $this->percentEncode(\substr($canonicalizedQueryString, 1));

        $signature = \base64_encode(\hash_hmac('sha1', $stringToSign, $this->accessKeySecret . '&', true));

        return $signature;
    }

    private function percentEncode(string $str): string
    {
        $res = \urlencode($str);
        $res = \str_replace(['+', '*'], ['%20', '%2A'], $res);
        $res = \preg_replace('/%7E/i', '~', $res);

        return $res;
    }
}
