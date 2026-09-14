<?php

declare(strict_types=1);

namespace Utopia\Messaging\Adapter\SMS\WhatsApp;

enum MetadataParameter: string
{
    /**
     * Template language code (for example `en_US` or `pt_BR`) overriding the adapter default for one message.
     * The authentication template must already exist in that language.
     */
    case LANGUAGE = 'language';
}
