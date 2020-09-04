<?php
/*
* File:     Client.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

use Webklex\IMAP\Exceptions\ConnectionFailedException;
use Webklex\IMAP\Exceptions\GetMessagesFailedException;
use Webklex\IMAP\Exceptions\InvalidImapTimeoutTypeException;
use Webklex\IMAP\Exceptions\MailboxFetchingException;
use Webklex\IMAP\Exceptions\MaskNotFoundException;
use Webklex\IMAP\Exceptions\MessageSearchValidationException;
use Webklex\IMAP\Support\FolderCollection;
use Webklex\IMAP\Support\Masks\AttachmentMask;
use Webklex\IMAP\Support\Masks\MessageMask;
use Webklex\IMAP\Support\MessageCollection;

/**
 * Class Client
 *
 * @package Webklex\IMAP
 */
class Client {

    /**
     * @var boolean|resource
     */
    public $connection = false;

    /**
     * Server hostname.
     *
     * @var string
     */
    public $host;

    /**
     * Server port.
     *
     * @var int
     */
    public $port;

    /**
     * Service protocol.
     *
     * @var int
     */
    public $protocol;

    /**
     * Server encryption.
     * Supported: none, ssl, tls, or notls.
     *
     * @var string
     */
    public $encryption;

    /**
     * If server has to validate cert.
     *
     * @var mixed
     */
    public $validate_cert;

    /**
     * Account username/
     *
     * @var mixed
     */
    public $username;

    /**
     * Account password.
     *
     * @var string
     */
    public $password;

    /**
     * Read only parameter.
     *
     * @var bool
     */
    protected $read_only = false;

    /**
     * Active folder.
     *
     * @var Folder
     */
    protected $active_folder = false;

    /**
     * Connected parameter
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * IMAP errors that might have ben occurred
     *
     * @var array $errors
     */
    protected $errors = [];

    /**
     * All valid and available account config parameters
     *
     * @var array $validConfigKeys
     */
    protected $valid_config_keys = ['host', 'port', 'encryption', 'validate_cert', 'username', 'password', 'protocol'];

    /**
     * @var string $default_message_mask
     */
    protected $default_message_mask = MessageMask::class;

    /**
     * @var string $default_attachment_mask
     */
    protected $default_attachment_mask = AttachmentMask::class;

    /**
     * Client constructor.
     * @param array $config
     *
     * @throws MaskNotFoundException
     */
    public function __construct($config = []) {
        $this->setConfig($config);
        $this->setMaskFromConfig($config);
    }

    /**
     * Client destructor
     */
    public function __destruct() {
        $this->disconnect();
    }

    /**
     * Set the Client configuration
     *
     * @param array $config
     *
     * @return self
     */
    public function setConfig(array $config) {
        $default_account = config('imap.default');
        $default_config  = config("imap.accounts.$default_account");

        foreach ($this->valid_config_keys as $key) {
            $this->$key = isset($config[$key]) ? $config[$key] : $default_config[$key];
        }

        return $this;
    }

    /**
     * Look for a possible mask in any available config
     * @param $config
     *
     * @throws MaskNotFoundException
     */
    protected function setMaskFromConfig($config) {
        $default_config  = config("imap.masks");

        if(isset($config['masks'])){
            if(isset($config['masks']['message'])) {
                if(class_exists($config['masks']['message'])) {
                    $this->default_message_mask = $config['masks']['message'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$config['masks']['message']);
                }
            }else{
                if(class_exists($default_config['message'])) {
                    $this->default_message_mask = $default_config['message'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$default_config['message']);
                }
            }
            if(isset($config['masks']['attachment'])) {
                if(class_exists($config['masks']['attachment'])) {
                    $this->default_message_mask = $config['masks']['attachment'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$config['masks']['attachment']);
                }
            }else{
                if(class_exists($default_config['attachment'])) {
                    $this->default_message_mask = $default_config['attachment'];
                }else{
                    throw new MaskNotFoundException("Unknown mask provided: ".$default_config['attachment']);
                }
            }
        }else{
            if(class_exists($default_config['message'])) {
                $this->default_message_mask = $default_config['message'];
            }else{
                throw new MaskNotFoundException("Unknown mask provided: ".$default_config['message']);
            }

            if(class_exists($default_config['attachment'])) {
                $this->default_message_mask = $default_config['attachment'];
            }else{
                throw new MaskNotFoundException("Unknown mask provided: ".$default_config['attachment']);
            }
        }

    }

    /**
     * Get the current imap resource
     *
     * @return bool|resource
     * @throws ConnectionFailedException
     */
    public function getConnection() {
        $this->checkConnection();
        return $this->connection;
    }

    /**
     * Set read only property and reconnect if it's necessary.
     *
     * @param bool $read_only
     *
     * @return self
     */
    public function setReadOnly($read_only = true) {
        $this->read_only = $read_only;

        return $this;
    }

    /**
     * Determine if connection was established.
     *
     * @return bool
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * Determine if connection is in read only mode.
     *
     * @return bool
     */
    public function isReadOnly() {
        return $this->read_only;
    }

    /**
     * Determine if connection was established and connect if not.
     *
     * @throws ConnectionFailedException
     */
    public function checkConnection() {
        if (!$this->isConnected() || $this->connection === false) {
            $this->connect();
        }
    }

    /**
     * Connect to server.
     *
     * @param int $attempts
     *
     * @return $this
     * @throws ConnectionFailedException
     */
    public function connect($attempts = 3) {
        $this->disconnect();

        try {
            $this->connection = \imap_open(
                $this->getAddress(),
                $this->username,
                $this->password,
                $this->getOptions(),
                $attempts,
                config('imap.options.open')
            );
            $this->connected = !!$this->connection;
        } catch (\ErrorException $e) {
            $errors = \imap_errors();
            $message = $e->getMessage().'. '.implode("; ", (is_array($errors) ? $errors : array()));

            throw new ConnectionFailedException($message);
        }

        return $this;
    }

    /**
     * Disconnect from server.
     *
     * @return $this
     */
    public function disconnect() {
        if ($this->isConnected() && $this->connection !== false && is_integer($this->connection) === false) {
            $this->errors = array_merge($this->errors, \imap_errors() ?: []);
            $this->connected = !\imap_close($this->connection, IMAP::CL_EXPUNGE);
        }

        return $this;
    }

    /**
     * Get a folder instance by a folder name
     * ---------------------------------------------
     * PLEASE NOTE: This is an experimental function
     * ---------------------------------------------
     * @param string        $folder_name
     * @param int           $attributes
     * @param null|string   $delimiter
     * @param boolean       $prefix_address
     *
     * @return Folder
     */
    public function getFolder($folder_name, $attributes = 32, $delimiter = null, $prefix_address = true) {

        $delimiter = $delimiter === null ? config('imap.options.delimiter', '/') : $delimiter;

        $folder_name = $prefix_address ? $this->getAddress().$folder_name : $folder_name;

        $oFolder = new Folder($this, (object) [
            'name'       => $folder_name,
            'attributes' => $attributes,
            'delimiter'  => $delimiter
        ]);

        return $oFolder;
    }

    /**
     * Get folders list.
     * If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.
     *
     * @param boolean     $hierarchical
     * @param string|null $parent_folder
     *
     * @return FolderCollection
     * @throws ConnectionFailedException
     * @throws MailboxFetchingException
     */
    public function getFolders($hierarchical = true, $parent_folder = null) {
        $this->checkConnection();
        $folders = FolderCollection::make([]);

        $pattern = $parent_folder.($hierarchical ? '%' : '*');

        $items = \imap_getmailboxes($this->connection, $this->getAddress(), $pattern);
        if(is_array($items)){
            foreach ($items as $item) {
                $folder = new Folder($this, $item);

                if ($hierarchical && $folder->hasChildren()) {
                    $pattern = $folder->getEncodedName().$folder->delimiter.'%';

                    $children = $this->getFolders(true, $pattern);
                    $folder->setChildren($children);
                }

                $folders->push($folder);
            }

            return $folders;
        }else{
            throw new MailboxFetchingException($this->getLastError());
        }
    }

    /**
     * Open folder.
     *
     * @param string|Folder $folder_path
     * @param int           $attempts
     *
     * @throws ConnectionFailedException
     */
    public function openFolder($folder_path, $attempts = 3) {
        $this->checkConnection();

        if(property_exists($folder_path, 'path')) {
            $folder_path = $folder_path->path;
        }

        if ($this->active_folder !== $folder_path) {
            $this->active_folder = $folder_path;

            \imap_reopen($this->getConnection(), $folder_path, $this->getOptions(), $attempts);
        }
    }

    /**
     * Create a new Folder
     * @param string $name
     * @param boolean $expunge
     *
     * @return bool
     * @throws ConnectionFailedException
     */
    public function createFolder($name, $expunge = true) {
        $this->checkConnection();
        $status = \imap_createmailbox($this->getConnection(), $this->getAddress() . \imap_utf7_encode($name));
        if($expunge) $this->expunge();

        return $status;
    }
    
    /**
     * Rename Folder
     * @param string  $old_name
     * @param string  $new_name
     * @param boolean $expunge
     *
     * @return bool
     * @throws ConnectionFailedException
     */
    public function renameFolder($old_name, $new_name, $expunge = true) {
        $this->checkConnection();
        $status = \imap_renamemailbox($this->getConnection(), $this->getAddress() . \imap_utf7_encode($old_name), $this->getAddress() . \imap_utf7_encode($new_name));
        if($expunge) $this->expunge();

        return $status;
    }
    
     /**
     * Delete Folder
     * @param string $name
      * @param boolean $expunge
     *
     * @return bool
     * @throws ConnectionFailedException
     */
    public function deleteFolder($name, $expunge = true) {
        $this->checkConnection();
        $status = \imap_deletemailbox($this->getConnection(), $this->getAddress() . \imap_utf7_encode($name));
        if($expunge) $this->expunge();

        return $status;
    }

    /**
     * Get messages from folder.
     *
     * @param Folder   $folder
     * @param string   $criteria
     * @param int|null $fetch_options
     * @param boolean  $fetch_body
     * @param boolean  $fetch_attachment
     * @param boolean  $fetch_flags
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws Exceptions\InvalidWhereQueryCriteriaException
     * @throws GetMessagesFailedException
     *
     * @deprecated 1.0.5.2:2.0.0 No longer needed. Use Folder::getMessages() instead
     * @see Folder::getMessages()
     */
    public function getMessages(Folder $folder, $criteria = 'ALL', $fetch_options = null, $fetch_body = null, $fetch_attachment = null, $fetch_flags = null) {
        return $folder->getMessages($criteria, $fetch_options, $fetch_body, $fetch_attachment, $fetch_flags);
    }

    /**
     * Get all unseen messages from folder
     *
     * @param Folder   $folder
     * @param string   $criteria
     * @param int|null $fetch_options
     * @param boolean  $fetch_body
     * @param boolean  $fetch_attachment
     * @param boolean  $fetch_flags
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws Exceptions\InvalidWhereQueryCriteriaException
     * @throws GetMessagesFailedException
     * @throws MessageSearchValidationException
     *
     * @deprecated 1.0.5:2.0.0 No longer needed. Use Folder::getMessages('UNSEEN') instead
     * @see Folder::getMessages()
     */
    public function getUnseenMessages(Folder $folder, $criteria = 'UNSEEN', $fetch_options = null, $fetch_body = true, $fetch_attachment = true, $fetch_flags = false) {
        return $folder->getUnseenMessages($criteria, $fetch_options, $fetch_body, $fetch_attachment, $fetch_flags);
    }

    /**
     * Search messages by a given search criteria
     *
     * @param array    $where
     * @param Folder   $folder
     * @param int|null $fetch_options
     * @param boolean  $fetch_body
     * @param string   $charset
     * @param boolean  $fetch_attachment
     * @param boolean  $fetch_flags
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws Exceptions\InvalidWhereQueryCriteriaException
     * @throws GetMessagesFailedException
     *
     * @deprecated 1.0.5:2.0.0 No longer needed. Use Folder::searchMessages() instead
     * @see Folder::searchMessages()
     *
     */
    public function searchMessages(array $where, Folder $folder, $fetch_options = null, $fetch_body = true, $charset = "UTF-8", $fetch_attachment = true, $fetch_flags = false) {
        return $folder->searchMessages($where, $fetch_options, $fetch_body, $charset, $fetch_attachment, $fetch_flags);
    }

    /**
     * Get option for \imap_open and \imap_reopen.
     * It supports only isReadOnly feature.
     *
     * @return int
     */
    protected function getOptions() {
        return ($this->isReadOnly()) ? IMAP::OP_READONLY : 0;
    }

    /**
     * Get full address of mailbox.
     *
     * @return string
     */
    protected function getAddress() {
        $address = "{".$this->host.":".$this->port."/".($this->protocol ? $this->protocol : 'imap');
        if (!$this->validate_cert) {
            $address .= '/novalidate-cert';
        }
        if (in_array($this->encryption,['tls', 'notls', 'ssl'])) {
            $address .= '/'.$this->encryption;
        } elseif ($this->encryption === "starttls") {
            $address .= '/tls';
        }

        $address .= '}';

        return $address;
    }

    /**
     * Retrieve the quota level settings, and usage statics per mailbox
     *
     * @return array
     * @throws ConnectionFailedException
     */
    public function getQuota() {
        $this->checkConnection();
        return \imap_get_quota($this->getConnection(), 'user.'.$this->username);
    }

    /**
     * Retrieve the quota settings per user
     *
     * @param string $quota_root
     *
     * @return array
     * @throws ConnectionFailedException
     */
    public function getQuotaRoot($quota_root = 'INBOX') {
        $this->checkConnection();
        return \imap_get_quotaroot($this->getConnection(), $quota_root);
    }

    /**
     * Gets the number of messages in the current mailbox
     *
     * @return int
     * @throws ConnectionFailedException
     */
    public function countMessages() {
        $this->checkConnection();
        return \imap_num_msg($this->connection);
    }

    /**
     * Gets the number of recent messages in current mailbox
     *
     * @return int
     * @throws ConnectionFailedException
     */
    public function countRecentMessages() {
        $this->checkConnection();
        return \imap_num_recent($this->connection);
    }

    /**
     * Read an overview of the information in the headers of a given message or sequence
     * @param string $sequence
     * @param int $option
     *
     * @return \Illuminate\Support\Collection
     * @throws ConnectionFailedException
     */
    public function overview($sequence = "1:*", $option = IMAP::NIL) {
        $this->checkConnection();
        return collect(\imap_fetch_overview($this->connection, $sequence, $option));
    }

    /**
     * Returns all IMAP alert messages that have occurred
     *
     * @return array
     */
    public function getAlerts() {
        return \imap_alerts();
    }

    /**
     * Returns all of the IMAP errors that have occurred
     *
     * @return array
     */
    public function getErrors() {
        $this->errors = array_merge($this->errors, \imap_errors() ?: []);

        return $this->errors;
    }

    /**
     * Gets the last IMAP error that occurred during this page request
     *
     * @return string
     */
    public function getLastError() {
        return \imap_last_error();
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return bool
     * @throws ConnectionFailedException
     */
    public function expunge() {
        $this->checkConnection();
        return \imap_expunge($this->connection);
    }

    /**
     * Check current mailbox
     *
     * @return object {
     *      Date    [string(37) "Wed, 8 Mar 2017 22:17:54 +0100 (CET)"]             current system time formatted according to » RFC2822
     *      Driver  [string(4) "imap"]                                              protocol used to access this mailbox: POP3, IMAP, NNTP
     *      Mailbox ["{root@example.com:993/imap/user="root@example.com"}INBOX"]    the mailbox name
     *      Nmsgs   [int(1)]                                                        number of messages in the mailbox
     *      Recent  [int(0)]                                                        number of recent messages in the mailbox
     * }
     * @throws ConnectionFailedException
     */
    public function checkCurrentMailbox() {
        $this->checkConnection();
        return \imap_check($this->connection);
    }

    /**
     * Set the imap timeout for a given operation type
     * @param $type
     * @param $timeout
     *
     * @return mixed
     * @throws InvalidImapTimeoutTypeException
     */
    public function setTimeout($type, $timeout) {
        if(0 <= $type && $type <= 4) {
            return \imap_timeout($type, $timeout);
        }

        throw new InvalidImapTimeoutTypeException("Invalid imap timeout type provided.");
    }

    /**
     * Get the timeout for a certain operation
     * @param $type
     *
     * @return mixed
     * @throws InvalidImapTimeoutTypeException
     */
    public function getTimeout($type){
        if(0 <= $type && $type <= 4) {
            return \imap_timeout($type);
        }

        throw new InvalidImapTimeoutTypeException("Invalid imap timeout type provided.");
    }

    /**
     * @return string
     */
    public function getDefaultMessageMask(){
        return $this->default_message_mask;
    }

    /**
     * @param $mask
     *
     * @return $this
     * @throws MaskNotFoundException
     */
    public function setDefaultMessageMask($mask) {
        if(class_exists($mask)) {
            $this->default_message_mask = $mask;

            return $this;
        }

        throw new MaskNotFoundException("Unknown mask provided: ".$mask);
    }

    /**
     * @return string
     */
    public function getDefaultAttachmentMask(){
        return $this->default_attachment_mask;
    }

    /**
     * @param $mask
     *
     * @return $this
     * @throws MaskNotFoundException
     */
    public function setDefaultAttachmentMask($mask) {
        if(class_exists($mask)) {
            $this->default_attachment_mask = $mask;

            return $this;
        }

        throw new MaskNotFoundException("Unknown mask provided: ".$mask);
    }

    /**
     * Get the current active folder
     *
     * @return Folder
     */
    public function getFolderPath(){
        return $this->active_folder;
    }
}
