<?php

namespace Utopia\Messaging;

interface LimitableSender
{
    /**
     * Get the maximum number of messages that can be sent in a single request.
     *
     * @return int
     */
    public function getMaxMessagesPerRequest(): int;
}