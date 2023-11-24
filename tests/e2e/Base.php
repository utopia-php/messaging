<?php

namespace Tests\E2E;

use PHPUnit\Framework\TestCase;

class Base extends TestCase
{
    protected function getLastRequest(): array
    {
        \sleep(2);

        $request = \json_decode(\file_get_contents('http://request-catcher:5000/__last_request__'), true);
        $request['data'] = \json_decode($request['data'], true);

        return $request;
    }

    protected function getLastEmail(): array
    {
        sleep(3);

        $emails = \json_decode(\file_get_contents('http://maildev:1080/email'), true);

        if ($emails && \is_array($emails)) {
            return \end($emails);
        }

        return [];
    }

    protected function assertResponse(array $response): void
    {
        $this->assertEquals(1, $response['deliveredTo']);
        $this->assertEquals('', $response['details'][0]['error']);
        $this->assertEquals('success', $response['details'][0]['status']);
    }
}
