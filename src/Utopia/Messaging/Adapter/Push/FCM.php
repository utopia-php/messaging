<?php

namespace Utopia\Messaging\Adapter\Push;

use Utopia\Messaging\Adapter\Push as PushAdapter;
use Utopia\Messaging\Helpers\JWT;
use Utopia\Messaging\Messages\Push as PushMessage;
use Utopia\Messaging\Response;

class FCM extends PushAdapter
{
    private const DEFAULT_EXPIRY_SECONDS = 3600;    // 1 hour

    private const DEFAULT_SKEW_SECONDS = 60;        // 1 minute

    private const GOOGLE_TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';

    /**
     * @param  string  $serviceAccountJSON Service account JSON file contents
     */
    public function __construct(
        private string $serviceAccountJSON,
    ) {
    }

    /**
     * Get adapter name.
     */
    public function getName(): string
    {
        return 'FCM';
    }

    /**
     * Get max messages per request.
     */
    public function getMaxMessagesPerRequest(): int
    {
        return 500;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(PushMessage $message): array
    {
        $credentials = \json_decode($this->serviceAccountJSON, true);

        $now = \time();

        $signingKey = $credentials['private_key'];
        $signingAlgorithm = 'RS256';

        $payload = [
            'iss' => $credentials['client_email'],
            'exp' => $now + self::DEFAULT_EXPIRY_SECONDS,
            'iat' => $now - self::DEFAULT_SKEW_SECONDS,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            //'aud' => self::GOOGLE_TOKEN_URL,
        ];

        $jwt = JWT::encode(
            $payload,
            $signingKey,
            $signingAlgorithm,
        );

        //        /**
        //         * @var array{
        //         *     refresh_token: ?string,
        //         *     expires_in: ?int,
        //         *     access_token: ?string,
        //         *     token_type: ?string,
        //         *     id_token: ?string
        //         * } $token
        //         */
        //        $token = $this->request(
        //            method: 'POST',
        //            url: self::GOOGLE_TOKEN_URL,
        //            headers: [
        //                'Content-Type: application/x-www-form-urlencoded',
        //                "Authorization: Bearer {$jwt}",
        //            ],
        //            body: \http_build_query([
        //                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        //                'assertion' => $jwt,
        //            ])
        //        )['response'];
        //
        //        $jwt = $token['access_token'];

        $shared = [
            'message' => [
                'notification' => [
                    'title' => $message->getTitle(),
                    'body' => $message->getBody(),
                ],
                'data' => $message->getData(),
            ],
        ];
        $androidNotification = [
            'click_action' => $message->getAction(),
            'icon' => $message->getIcon(),
            'color' => $message->getColor(),
            'sound' => $message->getSound(),
            'tag' => $message->getTag(),
            'image' => $message->getImage(),
        ];
        $apnsPayloadAps = [
            'category' => $message->getAction(),
            'badge' => $message->getBadge(),
            'sound' => $message->getSound(),
        ];
        $apnsFcmOptions = [
            'image' => $message->getImage(),
        ];

        if (! empty(array_filter($androidNotification))) {
            $shared['message']['android']['notification'] = $androidNotification;
        }
        if (! empty(array_filter($apnsPayloadAps))) {
            $shared['message']['apns']['payload']['aps'] = $apnsPayloadAps;
        }
        if (! empty(array_filter($apnsFcmOptions))) {
            $shared['message']['apns']['payload']['aps']['mutable-content'] = 1;
            $shared['message']['apns']['fcm_options'] = $apnsFcmOptions;
        }

        $bodies = [];

        foreach ($message->getTo() as $to) {
            $body = $shared;
            $body['message']['token'] = $to;
            $bodies[] = \json_encode($body);
        }

        $results = $this->requestMulti(
            method: 'POST',
            urls: ["https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send"],
            headers: [
                'Content-Type: application/json',
                "Authorization: Bearer {$jwt}",
            ],
            bodies: $bodies
        );

        $response = new Response($this->getType());

        foreach ($results as $index => $result) {
            $response->addResultForRecipient(
                $message->getTo()[$index],
                $this->getSpecificErrorMessage($result['error'])
            );
        }

        return $response->toArray();
    }

    private function getSpecificErrorMessage(string $error): string
    {
        return match ($error) {
            'MissingRegistration' => 'Bad Request. Missing token.',
            'InvalidRegistration' => 'Invalid device token.',
            'NotRegistered' => 'Expired device token.',
            'MessageTooBig' => 'Payload is too large. Messages must be less than 4096 bytes.',
            'DeviceMessageRateExceeded' => 'Too many requests were made to the same device token.',
            'InternalServerError' => 'Internal server error.',
            default => $error,
        };
    }
}
