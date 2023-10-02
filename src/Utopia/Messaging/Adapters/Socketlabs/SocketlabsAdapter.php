// SocketlabsAdapter.php

class SocketlabsAdapter implements MessagingAdapterInterface
{
    private $apiKey;
    private $apiEndpoint;

    public function setConfig($config)
    {
        // Set the configuration values.
        $this->apiKey = $config['apiKey'];
        $this->apiEndpoint = $config['apiEndpoint'];
    }

    public function sendMessage($message)
    {
        // Construct the email payload
        $emailData = [
            'subject' => $message->getSubject(),
            'htmlBody' => $message->getHtmlBody(),
            'textBody' => $message->getTextBody(),
            'from' => $message->getFrom(),
            'to' => $message->getTo(),
        ];

        // Convert data to JSON
        $jsonPayload = json_encode($emailData);

        // Prepare the HTTP request
        $ch = curl_init($this->apiEndpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);

        // Execute the request
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Handle the response and return results
        if ($statusCode === 200) {
            // Successfully sent
            return true;
        } else {
            // Failed to send, handle errors
            return false;
        }
    }
}
