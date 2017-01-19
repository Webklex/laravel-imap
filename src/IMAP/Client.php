<?php
/*
* File:     Client.php
* Category: Helper
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

use Illuminate\Support\Facades\Config;

use Webklex\IMAP\Exceptions\ConnectionFailedException;
use Webklex\IMAP\Exceptions\GetMessagesFailedException;

class Client
{
    /**
     * @var bool|resource
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
     * Server encryption.
     * Supported: none, ssl or tls.
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
    protected $activeFolder = false;

    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Set the Client configuration
     *
     * @param array $config
     */
    public function setConfig(array $config) {
        $defaultAccount = config('imap.default');
        $defaultConfig  = config("imap.accounts.$defaultAccount");

        foreach($defaultConfig as $key => $default){
            $this->$key = isset($config[$key]) ? $config[$key] : $default;
        }
    }

    /**
     * Set read only property and reconnect if it's necessary.
     *
     * @param bool $readOnly
     */
    public function setReadOnly($readOnly = true)
    {
        $this->read_only = $readOnly;
    }

    /**
     * Determine if connection was established.
     *
     * @return bool
     */
    public function isConnected()
    {
        return ($this->connection) ? true : false;
    }

    /**
     * Determine if connection is in read only mode.
     *
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->read_only;
    }

    /**
     * Determine if connection was established and connect if not.
     */
    public function checkConnection()
    {
        if (!$this->isConnected()) {
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
    public function connect($attempts = 3)
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }

        try {
            $this->connection = imap_open(
                $this->getAddress(),
                $this->username,
                $this->password,
                $this->getOptions(),
                $attempts
            );
        } catch (\ErrorException $e) {
            $message = $e->getMessage().'. '.implode("; ", imap_errors());

            throw new ConnectionFailedException($message);
        }

        return $this;
    }

    /**
     * Disconnect from server.
     *
     * @return $this
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            imap_close($this->connection);
        }

        return $this;
    }

    /**
     * Get folders list.
     * If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.
     *
     * @param bool $hierarchical
     * @param null $parent_folder
     *
     * @return array
     */
    public function getFolders($hierarchical = true, $parent_folder = null)
    {
        $this->checkConnection();
        $folders = [];

        if ($hierarchical) {
            $pattern = $parent_folder.'%';
        } else {
            $pattern = $parent_folder.'*';
        }

        $items = imap_getmailboxes($this->connection, $this->getAddress(), $pattern);
        foreach ($items as $item) {
            $folder = new Folder($this, $item);

            if ($hierarchical && $folder->hasChildren()) {
                $pattern = $folder->fullName.$folder->delimiter.'%';

                $children = $this->getFolders(true, $pattern);
                $folder->setChildren($children);
            }
            $folders[] = $folder;
        }

        return $folders;
    }

    /**
     * Open folder.
     *
     * @param Folder $folder
     */
    public function openFolder(Folder $folder)
    {
        $this->checkConnection();

        if ($this->activeFolder != $folder) {
            $this->activeFolder = $folder;

            imap_reopen($this->connection, $folder->path, $this->getOptions(), 3);
        }
    }

    /**
     * Get messages from folder.
     *
     * @param Folder $folder
     * @param string $criteria
     *
     * @return array
     * @throws GetMessagesFailedException
     */
    public function getMessages(Folder $folder, $criteria = 'ALL')
    {
        $this->checkConnection();

        try {
            $this->openFolder($folder);
            $messages = [];
            $availableMessages = imap_search($this->connection, $criteria, SE_UID);

            if ($availableMessages !== false) {
                foreach ($availableMessages as $msgno) {
                    $message = new Message($msgno, $this);

                    $messages[$message->message_id] = $message;
                }
            }
            return $messages;
        } catch (\Exception $e) {
            $message = $e->getMessage();

            throw new GetMessagesFailedException($message);
        }
    }

    /**
     * Get option for imap_open and imap_reopen.
     * It supports only isReadOnly feature.
     *
     * @return int
     */
    protected function getOptions()
    {
        return ($this->isReadOnly()) ? OP_READONLY : 0;
    }

    /**
     * Get full address of mailbox.
     *
     * @return string
     */
    protected function getAddress()
    {
        $address = "{".$this->host.":".$this->port."/imap";
        if (!$this->validate_cert) {
            $address .= '/novalidate-cert';
        }
        if ($this->encryption == 'ssl') {
            $address .= '/ssl';
        }
        $address .= '}';

        return $address;
    }
}
