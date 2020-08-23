<?php

namespace Webklex\IMAP\Events;

use Webklex\IMAP\Message;

class MessageMovedEvent extends Event {

    /** @var Message $old_message */
    public $old_message;
    /** @var Message $new_message */
    public $new_message;

    /**
     * Create a new event instance.
     * @var Message $old_message
     * @var Message $new_message
     * @return void
     */
    public function __construct($old_message, $new_message) {
        $this->old_message = $old_message;
        $this->new_message = $new_message;
    }
}
