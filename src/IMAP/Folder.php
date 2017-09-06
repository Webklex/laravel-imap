<?php
/*
* File:     Folder.php
* Category: Helper
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

/**
 * Class Folder
 *
 * @package Webklex\IMAP
 */
class Folder {

    /**
     * Client instance
     *
     * @var \Webklex\IMAP\Client
     */
    protected $client;

    /**
     * Folder full path
     *
     * @var string
     */
    public $path;

    /**
     * Folder name
     *
     * @var string
     */
    public $name;

    /**
     * Folder fullname
     *
     * @var string
     */
    public $fullName;

    /**
     * Children folders
     *
     * @var array
     */
    public $children = [];

    /**
     * Delimiter for folder
     *
     * @var string
     */
    public $delimiter;

    /**
     * Indicates if folder can't containg any "children".
     * CreateFolder won't work on this folder.
     *
     * @var boolean
     */
    public $no_inferiors;

    /**
     * Indicates if folder is only container, not a mailbox - you can't open it.
     *
     * @var boolean
     */
    public $no_select;

    /**
     * Indicates if folder is marked. This means that it may contain new messages since the last time it was checked.
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $marked;

    /**
     * Indicates if folder containg any "children".
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $has_children;

    /**
     * Indicates if folder refers to other.
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $referal;

    /**
     * Folder constructor.
     *
     * @param \Webklex\IMAP\Client $client
     *
     * @param object $folder
     */
    public function __construct(Client $client, $folder) {
        $this->client = $client;

        $this->delimiter = $folder->delimiter;
        $this->path      = $folder->name;
        $this->fullName  = $this->decodeName($folder->name);
        $this->name      = $this->getSimpleName($this->delimiter, $this->fullName);

        $this->parseAttributes($folder->attributes);
    }

    /**
     * Determine if folder has children.
     *
     * @return bool
     */
    public function hasChildren() {
        return $this->has_children;
    }

    /**
     * Set children.
     *
     * @param array $children
     *
     * @return self
     */
    public function setChildren($children = []) {
        $this->children = $children;

        return $this;
    }

    /**
     * Get messages.
     *
     * @param string $criteria
     * @param null   $fetch_options
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMessages($criteria = 'ALL', $fetch_options = null) {
        return collect($this->client->getMessages($this, $criteria, $fetch_options));
    }

    /**
     * Decode name.
     * It converts UTF7-IMAP encoding to UTF-8.
     *
     * @param $name
     *
     * @return mixed|string
     */
    protected function decodeName($name) {
        preg_match('#\{(.*)\}(.*)#', $name, $preg);
        return mb_convert_encoding($preg[2], "UTF-8", "UTF7-IMAP");
    }

    /**
     * Get simple name (without parent folders).
     *
     * @param $delimiter
     * @param $fullName
     *
     * @return mixed
     */
    protected function getSimpleName($delimiter, $fullName) {
        $arr = explode($delimiter, $fullName);

        return end($arr);
    }

    /**
     * Parse attributes and set it to object properties.
     *
     * @param $attributes
     */
    protected function parseAttributes($attributes) {
        $this->no_inferiors = ($attributes & LATT_NOINFERIORS)  ? true : false;
        $this->no_select    = ($attributes & LATT_NOSELECT)     ? true : false;
        $this->marked       = ($attributes & LATT_MARKED)       ? true : false;
        $this->referal      = ($attributes & LATT_REFERRAL)     ? true : false;
        $this->has_children = ($attributes & LATT_HASCHILDREN)  ? true : false;
    }

    /**
     * Delete the current Mailbox
     *
     * @return bool
     */
    public function delete(){
        $status = imap_deletemailbox($this->client->connection, $this->path);
        $this->client->expunge();

        return $status;
    }

    /**
     * Move or Rename the current Mailbox
     *
     * @param string $target_mailbox
     *
     * @return bool
     */
    public function move($target_mailbox){
        $status = imap_renamemailbox($this->client->connection, $this->path, $target_mailbox);
        $this->client->expunge();

        return $status;
    }

    /**
     * Returns status information on a mailbox
     *
     * @param string    $options
     *                  SA_MESSAGES     - set $status->messages to the number of messages in the mailbox
     *                  SA_RECENT       - set $status->recent to the number of recent messages in the mailbox
     *                  SA_UNSEEN       - set $status->unseen to the number of unseen (new) messages in the mailbox
     *                  SA_UIDNEXT      - set $status->uidnext to the next uid to be used in the mailbox
     *                  SA_UIDVALIDITY  - set $status->uidvalidity to a constant that changes when uids for the mailbox may no longer be valid
     *                  SA_ALL          - set all of the above
     *
     * @return object
     */
    public function getStatus($options){
        return imap_status($this->client->connection, $this->path, $options);
    }

    /**
     * Append a string message to the current mailbox
     *
     * @param string $message
     * @param string $options
     * @param string $internal_date
     *
     * @return bool
     */
    public function appendMessage($message, $options = null, $internal_date = null){
        return imap_append($this->client->connection, $this->path, $message, $options, $internal_date);
    }
}