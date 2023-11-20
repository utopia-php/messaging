# Utopia Messaging

[![Build Status](https://travis-ci.org/utopia-php/abuse.svg?branch=master)](https://travis-ci.com/utopia-php/database)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/messaging.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://appwrite.io/discord)

Utopia Messaging library is simple and lite library for sending messages using multiple messaging adapters. This library is aiming to be as simple and easy to learn and use. This library is maintained by the [Appwrite team](https://appwrite.io).

Although this library is part of the [Utopia Framework](https://github.com/utopia-php/framework) project it is dependency free, and can be used as standalone with any other PHP project or framework.

## Getting Started

Install using composer:
```bash
composer require utopia-php/messaging
```

## Email 

```php
<?php

use \Utopia\Messaging\Messages\Email;
use \Utopia\Messaging\Adapters\Email\SendGrid;
use \Utopia\Messaging\Adapters\Email\Mailgun;

$message = new Email(
    to: ['team@appwrite.io'],
    subject: 'Hello World',
    content: '<h1>Hello World</h1>'
);

$messaging = new Sendgrid('YOUR_API_KEY');
$messaging->send($message);

$messaging = new Mailgun('YOUR_API_KEY', 'YOUR_DOMAIN');
$messaging->send($message);
```

## SMS

```php
<?php

use \Utopia\Messaging\Messages\SMS;
use \Utopia\Messaging\Adapters\SMS\Twilio;
use \Utopia\Messaging\Adapters\SMS\Telesign;

$message = new SMS(
    to: ['+12025550139'],
    content: 'Hello World'
);

$messaging = new Twilio('YOUR_ACCOUNT_SID', 'YOUR_AUTH_TOKEN');
$messaging->send($message);

$messaging = new Telesign('YOUR_USERNAME', 'YOUR_PASSWORD');
$messaging->send($message);
```

## Push

```php
<?php

use \Utopia\Messaging\Messages\Push;
use \Utopia\Messaging\Adapters\Push\FCM;

$message = new Push(
    to: ['eyJhGc...ssw5c'],
    content: 'Hello World'
);

$messaging = new FCM('YOUR_SERVER_KEY');
$messaging->send($message);
```

## Adapters

> Want to implement any of the missing adapters or have an idea for another? We would love to hear from you! Please check out our [contribution guide](./CONTRIBUTING.md) and [new adapter guide](./docs/add-new-adapter.md) for more information.

### Email
- [x] [SendGrid](https://sendgrid.com/)
- [x] [Mailgun](https://www.mailgun.com/)
- [ ] [Mailjet](https://www.mailjet.com/)
- [ ] [Mailchimp](https://www.mailchimp.com/)
- [ ] [Postmark](https://postmarkapp.com/)
- [ ] [SparkPost](https://www.sparkpost.com/)
- [ ] [SendinBlue](https://www.sendinblue.com/)
- [ ] [MailSlurp](https://www.mailslurp.com/)
- [ ] [ElasticEmail](https://elasticemail.com/)
- [ ] [SES](https://aws.amazon.com/ses/)

### SMS
- [x] [Twilio](https://www.twilio.com/)
- [x] [Twilio Notify](https://www.twilio.com/notify)
- [x] [Telesign](https://www.telesign.com/)
- [x] [TextMagic](https://www.textmagic.com/)
- [x] [Msg91](https://msg91.com/)
- [x] [Vonage](https://www.vonage.com/)
- [x] [Plivo](https://www.plivo.com/)
- [x] [Infobip](https://www.infobip.com/)
- [x] [Clickatell](https://www.clickatell.com/)
- [ ] [AfricasTalking](https://africastalking.com/)
- [x] [Sinch](https://www.sinch.com/)
- [x] [Seven](https://www.seven.io/)
- [ ] [SmsGlobal](https://www.smsglobal.com/)

### Push
- [x] [FCM](https://firebase.google.com/docs/cloud-messaging)
- [ ] [APNS](https://developer.apple.com/documentation/usernotifications)
- [ ] [OneSignal](https://onesignal.com/)
- [x] [Pusher](https://pusher.com/)
- [ ] [WebPush](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
- [ ] [UrbanAirship](https://www.urbanairship.com/)
- [ ] [Pushwoosh](https://www.pushwoosh.com/)
- [ ] [PushBullet](https://www.pushbullet.com/)
- [ ] [Pushy](https://pushy.me/)

## System Requirements

Utopia Messaging requires PHP 8.0 or later. We recommend using the latest PHP version whenever possible.

## Tests

To run all unit tests, use the following Docker command:

```bash
composer test
```

To run static code analysis, use the following Psalm command:

```bash
composer lint
```

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
