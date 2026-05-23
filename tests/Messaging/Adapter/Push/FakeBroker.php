<?php

require __DIR__ . '/../../../../vendor/autoload.php';

use Utopia\Messaging\Helpers\MQTT;

[$_, $port, $capturePath, $stateFile] = $argv;
$port = (int)$port;
$state = \json_decode(\file_get_contents($stateFile), true) ?: [];
$rejectTokens = $state['reject'] ?? [];

$captured = [
    'connect' => [],
    'publishes' => [],
];

\register_shutdown_function(function () use (&$captured, $capturePath) {
    \file_put_contents($capturePath, \json_encode($captured));
});

\pcntl_async_signals(true);
foreach ([SIGTERM, SIGINT] as $signal) {
    \pcntl_signal($signal, function () use (&$captured, $capturePath) {
        \file_put_contents($capturePath, \json_encode($captured));
        exit(0);
    });
}

$server = \stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);
if (!$server) {
    \fwrite(STDERR, "Could not bind: {$errstr}\n");
    exit(1);
}

\stream_set_blocking($server, false);

// @phpstan-ignore-next-line
while (true) { // Exits only via SIGTERM handler above.
    $client = @\stream_socket_accept($server, 5);
    if (!$client) {
        continue;
    }

    \stream_set_timeout($client, 5);

    $buffer = '';

    while (!\feof($client)) {
        $chunk = @\fread($client, 4096);
        if ($chunk === '' || $chunk === false) {
            $info = \stream_get_meta_data($client);
            if (!empty($info['timed_out'])) {
                break;
            }
            continue;
        }

        $buffer .= $chunk;

        while (($packet = MQTT::decodePacket($buffer)) !== null) {
            switch ($packet['type']) {
                case MQTT::PACKET_CONNECT:
                    $parsed = MQTT::parseConnect($packet['payload']);
                    $captured['connect'] = [
                        'clientId' => $parsed['clientId'],
                        'username' => (string)$parsed['username'],
                        'password' => (string)$parsed['password'],
                    ];
                    \fwrite($client, MQTT::encodeConnack(MQTT::REASON_SUCCESS));
                    break;

                case MQTT::PACKET_PUBLISH:
                    $parsed = MQTT::parsePublish($packet['payload'], $packet['flags']);
                    $captured['publishes'][] = [
                        'topic' => $parsed['topic'],
                        'payload' => $parsed['payload'],
                        'qos' => $parsed['qos'],
                    ];

                    $reason = MQTT::REASON_SUCCESS;
                    foreach ($rejectTokens as $bad) {
                        if (\str_ends_with($parsed['topic'], '/' . $bad)) {
                            $reason = 0x10;
                            break;
                        }
                    }

                    if ($parsed['qos'] === 1 && $parsed['packetId'] !== null) {
                        \fwrite($client, MQTT::encodePuback($parsed['packetId'], $reason));
                    }
                    break;

                case MQTT::PACKET_DISCONNECT:
                    @\fclose($client);
                    break 3;

                case MQTT::PACKET_PINGREQ:
                    \fwrite($client, MQTT::encodePingresp());
                    break;

                default:
                    break;
            }
        }
    }

    @\fclose($client);
    \file_put_contents($capturePath, \json_encode($captured));
}
