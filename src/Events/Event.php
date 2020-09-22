<?php

namespace Webklex\IMAP\Events;


abstract class Event {

    /**
     * Dispatch the event with the given arguments.
     *
     * @return void|array
     */
    public static function dispatch() {
        return event(new static(func_get_args()));
    }
}
