<?php

namespace Webklex\IMAP\Events;

use Webklex\PHPIMAP\Folder;

class FolderMovedEvent extends Event {

    /** @var Folder $old_folder */
    public $old_folder;
    /** @var Folder $new_folder */
    public $new_folder;

    /**
     * Create a new event instance.
     * @var Folder[] $folders
     * @return void
     */
    public function __construct($folders) {
        $this->old_folder = $folders[0];
        $this->new_folder = $folders[1];
    }
}
