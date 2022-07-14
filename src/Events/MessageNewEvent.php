<?php

namespace Grkztd\IMAP\Events;

use Grkztd\PHPIMAP\Message;

class MessageNewEvent extends Event {

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
