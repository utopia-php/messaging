<?php

namespace Utopia\Messaging\Apple;

class Notification
{
    private $certificatePath;
    private $passphrase;
    private $ssl;

    public function __construct($certificatePath, $passphrase, $ssl = 'ssl://gateway.push.apple.com:2195')
    {
        $this->certificatePath = $certificatePath;
        $this->passphrase = $passphrase;
        $this->ssl = $ssl;
    }

    public function send($deviceToken, $message)
    {
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->certificatePath);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->passphrase);

        $fp = stream_socket_client(
            $this->ssl, $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx
        );

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

        $body['aps'] = array(
            'alert' => $message,
            'sound' => 'default'
        );

        $payload = json_encode($body);

        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
        $result = fwrite($fp, $msg, strlen($msg));

        if (!$result)
            echo 'Message not delivered' . PHP_EOL;
        else
            echo 'Message successfully delivered' . PHP_EOL;

        fclose($fp);
    }
}

?>
