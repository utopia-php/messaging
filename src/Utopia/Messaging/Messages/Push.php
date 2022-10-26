<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class Push implements Message
{
    /**
     * @param array<string> $to The recipients of the push notification.
     * @param string $title The title of the push notification.
     * @param string $body The body of the push notification.
     * @param array<string>|null $data This parameter specifies the custom key-value pairs of the message's payload. For example, with data:{"score":"3x1"}:<br><br>On Apple platforms, if the message is sent via APNs, it represents the custom data fields. If it is sent via FCM, it would be represented as key value dictionary in AppDelegate application:didReceiveRemoteNotification:.<br><br>On Android, this would result in an intent extra named score with the string value 3x1.<br><br>The key should not be a reserved word ("from", "message_type", or any word starting with "google" or "gcm"). Do not use any of the words defined in this table (such as collapse_key).<br><br>Values in string types are recommended. You have to convert values in objects or other non-string data types (e.g., integers or booleans) to string.
     * @param string|null $sound The sound to play when the device receives the notification.<br><br>On Android, sound files must reside in /res/raw/.<br><br>On iOS, sounds files must reside in the main bundle of the client app or in the Library/Sounds folder of the app's data container.
     * @param string|null $action The action associated with a user click on the notification.<br><br>On Android, this is the activity to launch.<br><br>On iOS, this is the category to launch.
     * @param string|null $icon <b>Android only</b>. The icon of the push notification. Sets the notification icon to myicon for drawable resource myicon. If you don't send this key in the request, FCM displays the launcher icon specified in your app manifest.
     * @param string|null $color <b>Android only</b>. The icon color of the push notification, expressed in #rrggbb format.
     * @param string|null $tag <b>Android only</b>. Identifier used to replace existing notifications in the notification drawer.<br><br>If not specified, each request creates a new notification.<br><br>If specified and a notification with the same tag is already being shown, the new notification replaces the existing one in the notification drawer.
     * @param string|null $badge <b>iOS only</b>. The value of the badge on the home screen app icon. If not specified, the badge is not changed. If set to 0, the badge is removed.
     */
    public function __construct(
        private array $to,
        private string $title,
        private string $body,
        private ?array $data = null,
        private ?string $action = null,
        private ?string $sound = null,
        private ?string $icon = null,
        private ?string $color = null,
        private ?string $tag = null,
        private ?string $badge = null,
    ) {
    }

    /**
     * @return array<string>
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getSound(): ?string
    {
        return $this->sound;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @return string|null
     */
    public function getBadge(): ?string
    {
        return $this->badge;
    }
}
