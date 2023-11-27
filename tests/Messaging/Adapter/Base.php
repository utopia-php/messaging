<?php

namespace Utopia\Tests\Adapter;

use PHPUnit\Framework\TestCase;

class Base extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function getLastRequest(): array
    {
        \sleep(2);

        $request = \json_decode(\file_get_contents('http://request-catcher:5000/__last_request__'), true);
        $request['data'] = \json_decode($request['data'], true);

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getLastEmail(): array
    {
        sleep(3);

        $emails = \json_decode(\file_get_contents('http://maildev:1080/email'), true);

        if ($emails && \is_array($emails)) {
            return \end($emails);
        }

        return [];
    }
}
