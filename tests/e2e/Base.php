<?php

namespace Tests\E2E;

use PHPUnit\Framework\TestCase;

class Base extends TestCase
{
    /**
     * @return mixed
     */
    protected function getLastRequest(): mixed
    {
        \sleep(2);

        $request = \json_decode(\file_get_contents('http://request-catcher:5000/__last_request__'), true);
        $request['data'] = \json_decode($request['data'], true);

        return $request;
    }
    
    /**
     * @return mixed
     */
    protected function getLastEmail(): mixed
    {
        sleep(3);

        $emails = \json_decode(\file_get_contents('http://maildev:1080/email'), true);

        if ($emails && \is_array($emails)) {
            return \end($emails);
        }

        return [];
    }
}
