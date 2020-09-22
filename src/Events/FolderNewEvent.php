<?php

namespace Webklex\IMAP\Events;

use Webklex\PHPIMAP\Folder;

class FolderNewEvent extends Event {

    /** @var Folder $folder */
    public $folder;

    /**
     * Create a new event instance.
     * @var Folder[] $folders
     * @return void
     */
    public function __construct($folders) {
        $this->folder = $folders[0];
    }
}
