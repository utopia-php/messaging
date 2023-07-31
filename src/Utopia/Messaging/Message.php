<?php

namespace Utopia\Messaging;

/**
 * Marker interface for specific message types.
 */
interface Message
{
    function getTo():array;
    function getFrom():?string;
}
