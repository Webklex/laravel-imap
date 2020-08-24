<?php

namespace Webklex\IMAP\Events;

use Webklex\IMAP\Message;

class MessageDeletedEvent extends Event {

    /** @var Message $message */
    public $message;

    /**
     * Create a new event instance.
     * @var Message[] $messages
     * @return void
     */
    public function __construct($messages) {
        $this->message = $messages[0];
    }
}
