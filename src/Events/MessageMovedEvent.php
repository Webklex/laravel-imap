<?php

namespace Webklex\IMAP\Events;

use Webklex\PHPIMAP\Message;

class MessageMovedEvent extends Event {

    /** @var Message $old_message */
    public $old_message;
    /** @var Message $new_message */
    public $new_message;

    /**
     * Create a new event instance.
     * @var Message[] $messages
     * @return void
     */
    public function __construct($messages) {
        $this->old_message = $messages[0];
        $this->new_message = $messages[1];
    }
}
