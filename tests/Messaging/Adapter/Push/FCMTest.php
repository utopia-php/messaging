<?php

namespace Utopia\Tests\Adapter\Push;

use PHPUnit\Framework\Attributes\DataProvider;
use Utopia\Messaging\Adapter\Push\FCM as FCMAdapter;

class FCMTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $serverKey = \getenv('FCM_SERVICE_ACCOUNT_JSON');

        $this->adapter = new FCMAdapter($serverKey);
    }

    protected function getTo(): array
    {
        return [\getenv('FCM_TO')];
    }

    /**
     * @param array{
     *     statusCode: int,
     *     response: array<string, mixed>|string|null,
     *     error: string|null,
     *     errorCode: int
     * } $result
     */
    #[DataProvider('errorProvider')]
    public function testGetError(array $result, string $expected): void
    {
        $adapter = new class ('{}') extends FCMAdapter {
            /**
             * @param array{
             *     statusCode: int,
             *     response: array<string, mixed>|string|null,
             *     error: string|null,
             *     errorCode: int
             * } $result
             */
            public function error(array $result): string
            {
                return $this->getError($result);
            }
        };

        $this->assertSame($expected, $adapter->error($result));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function errorProvider(): array
    {
        return [
            'firebase error message' => [
                ['statusCode' => 400, 'response' => ['error' => ['message' => 'Invalid registration token']], 'error' => '', 'errorCode' => 0],
                'Invalid registration token',
            ],
            'expired token' => [
                ['statusCode' => 404, 'response' => ['error' => ['status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.']], 'error' => '', 'errorCode' => 0],
                'Expired device token',
            ],
            'transport error' => [
                ['statusCode' => 0, 'response' => null, 'error' => 'Connection timed out after 10000 milliseconds', 'errorCode' => 28],
                'Connection timed out after 10000 milliseconds (HTTP status 0; cURL error code 28)',
            ],
            'no error message' => [
                ['statusCode' => 503, 'response' => 'Service Unavailable', 'error' => '', 'errorCode' => 0],
                'Request failed (HTTP status 503; cURL error code 0)',
            ],
        ];
    }
}
