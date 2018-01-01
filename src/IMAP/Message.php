<?php
/*
* File:     Message.php
* Category: Helper
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

use Carbon\Carbon;

/**
 * Class Message
 *
 * @package Webklex\IMAP
 */
class Message {

    /**
     * Client instance
     *
     * @var \Webklex\IMAP\Client
     */
    private $client = Client::class;

    /**
     * U ID
     *
     * @var string
     */
    public $uid = '';

    /**
     * Fetch body options
     *
     * @var string
     */
    public $fetch_options = null;

    /**
     * @var int $msglist
     */
    public $msglist = 1;

    /**
     * Message header components
     *
     * @var string  $message_id
     * @var mixed   $message_no
     * @var string  $subject
     * @var mixed   $date
     * @var array   $from
     * @var array   $to
     * @var array   $cc
     * @var array   $bcc
     * @var array   $reply_to
     * @var array   $sender
     */
    public $message_id = '';
    public $message_no = null;
    public $subject = '';
    public $date = null;
    public $from = [];
    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $reply_to = [];
    public $sender = [];

    /**
     * Message body components
     *
     * @var array   $bodies
     * @var array   $attachments
     */
    public $bodies = [];
    public $attachments = [];

    /**
     * Message const
     *
     * @const integer   TYPE_TEXT
     * @const integer   TYPE_MULTIPART
     * @const integer   TYPE_MESSAGE
     * @const integer   TYPE_APPLICATION
     * @const integer   TYPE_AUDIO
     * @const integer   TYPE_IMAGE
     * @const integer   TYPE_VIDEO
     * @const integer   TYPE_MODEL
     * @const integer   TYPE_OTHER
     *
     * @const integer   ENC_7BIT
     * @const integer   ENC_8BIT
     * @const integer   ENC_BINARY
     * @const integer   ENC_BASE64
     * @const integer   ENC_QUOTED_PRINTABLE
     * @const integer   ENC_OTHER
     */
    const TYPE_TEXT = 0;
    const TYPE_MULTIPART = 1;
    const TYPE_MESSAGE = 2;
    const TYPE_APPLICATION = 3;
    const TYPE_AUDIO = 4;
    const TYPE_IMAGE = 5;
    const TYPE_VIDEO = 6;
    const TYPE_MODEL = 7;
    const TYPE_OTHER = 8;

    const ENC_7BIT = 0;
    const ENC_8BIT = 1;
    const ENC_BINARY = 2;
    const ENC_BASE64 = 3;
    const ENC_QUOTED_PRINTABLE = 4;
    const ENC_OTHER = 5;

    /**
     * Message constructor.
     *
     * @param $uid
     * @param $msglist
     * @param \Webklex\IMAP\Client $client
     * @param $fetch_options
     */
    public function __construct($uid, $msglist, Client $client, $fetch_options = null) {
        $this->setFetchOption($fetch_options);
        
        $this->msglist = $msglist;
        $this->client = $client;
        $this->uid = ($this->fetch_options == FT_UID) ? $uid : imap_msgno($this->client->getConnection(), $uid);
        
        $this->parseHeader();
        $this->parseBody();
    }

    /**
     * Copy the current Messages to a mailbox
     *
     * @param $mailbox
     * @param int $options
     *
     * @return bool
     */
    public function copy($mailbox, $options = 0){
        return imap_mail_copy($this->client->connection, $this->msglist, $mailbox, $options);
    }

    /**
     * Move the current Messages to a mailbox
     *
     * @param $mailbox
     * @param int $options
     *
     * @return bool
     */
    public function move($mailbox, $options = 0){
        return imap_mail_move($this->client->connection, $this->msglist, $mailbox, $options);
    }

    /**
     * Check if the Message has a text body
     *
     * @return bool
     */
    public function hasTextBody() {
        return isset($this->bodies['text']);
    }

    /**
     * Get the Message text body
     *
     * @return mixed
     */
    public function getTextBody() {
        if (!isset($this->bodies['text'])) {
            return false;
        }

        return $this->bodies['text']->content;
    }

    /**
     * Check if the Message has a html body
     *
     * @return bool
     */
    public function hasHTMLBody() {
        return isset($this->bodies['html']);
    }

    /**
     * Get the Message html body
     *
     * @var bool $replaceImages
     *
     * @return mixed
     */
    public function getHTMLBody($replaceImages = false) {
        if (!isset($this->bodies['html'])) {
            return false;
        }

        $body = $this->bodies['html']->content;
        if ($replaceImages) {
            foreach ($this->attachments as $attachment) {
                if ($attachment->id && isset($attachment->img_src)){
                    $body = str_replace('cid:'.$attachment->id, $attachment->img_src, $body);
                }
            }
        }

        return $body;
    }

    /**
     * Parse all defined headers
     *
     * @return void
     */
    private function parseHeader() {
        $header = imap_fetchheader($this->client->connection, $this->uid, $this->fetch_options);
        if ($header) {
            $header = imap_rfc822_parse_headers($header);
        }

        if (property_exists($header, 'subject')) {
            $this->subject = imap_utf8($header->subject);
        }
        if (property_exists($header, 'date')) {
            $date = $header->date;

            /**
             * Exception handling for invalid dates
             * Will be extended in the future
             *
             * Currently known invalid formats:
             * ^ Datetime                                   ^ Problem                           ^ Cause                 ^
             * | Mon, 20 Nov 2017 20:31:31 +0800 (GMT+8:00) | Double timezone specification     | A Windows feature     |
             * |                                            | and invalid timezone (max 6 char) |                       |
             *
             * Please report any new invalid timestamps to [#45](https://github.com/Webklex/laravel-imap/issues/45)
             */
            try{
                $this->date = Carbon::parse($date);
            }catch(\Exception $e){
                switch(true){
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ \+[0-9]{4}\ \([A-Z]{2,3}\+[0-9]{1,2}\:[0-9]{1,2})\)+$/i', $date):
                        $array = explode('(', $date);
                        $array = array_reverse($array);
                        $date = trim(array_pop($array));
                        break;
                }
                $this->date = Carbon::parse($date);
            }
        }

        if (property_exists($header, 'from')) {
            $this->from = $this->parseAddresses($header->from);
        }
        if (property_exists($header, 'to')) {
            $this->to = $this->parseAddresses($header->to);
        }
        if (property_exists($header, 'cc')) {
            $this->cc = $this->parseAddresses($header->cc);
        }
        if (property_exists($header, 'bcc')) {
            $this->bcc = $this->parseAddresses($header->bcc);
        }

        if (property_exists($header, 'reply_to')) {
            $this->reply_to = $this->parseAddresses($header->reply_to);
        }
        if (property_exists($header, 'sender')) {
            $this->sender = $this->parseAddresses($header->sender);
        }

        if (property_exists($header, 'message_id')) {
            $this->message_id = str_replace(['<', '>'], '', $header->message_id);
        }
        if (property_exists($header, 'Msgno')) {
            $this->message_no = ($this->fetch_options == FT_UID) ? trim($header->Msgno) : imap_msgno($this->client->getConnection(), trim($header->Msgno));
        }
    }

    /**
     * Parse Addresses
     *
     * @param $list
     *
     * @return array
     */
    private function parseAddresses($list) {
        $addresses = [];

        foreach ($list as $item) {
            $address = (object) $item;

            if (!property_exists($address, 'mailbox')) {
                $address->mailbox = false;
            }
            if (!property_exists($address, 'host')) {
                $address->host = false;
            }
            if (!property_exists($address, 'personal')) {
                $address->personal = false;
            }

            $address->personal = imap_utf8($address->personal);

            $address->mail = ($address->mailbox && $address->host) ? $address->mailbox . '@' . $address->host : false;
            $address->full = ($address->personal) ? $address->personal.' <'.$address->mail.'>' : $address->mail;

            $addresses[] = $address;
        }

        return $addresses;
    }

    /**
     * Parse the Message body
     *
     * @return void
     */
    private function parseBody() {
        $structure = imap_fetchstructure($this->client->connection, $this->uid, $this->fetch_options);

        $this->fetchStructure($structure);
    }

    /**
     * Fetch the Message structure
     *
     * @param $structure
     * @param mixed $partNumber
     */
    private function fetchStructure($structure, $partNumber = null) {
        if ($structure->type == self::TYPE_TEXT && 
            ($structure->ifdisposition == 0 || 
                ($structure->ifdisposition == 1 && !isset($structure->parts) && $partNumber == null)
            )
        ) {
            if ($structure->subtype == "PLAIN") {
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->connection, $this->uid, $partNumber, $this->fetch_options);
                $content = $this->decodeString($content, $structure->encoding);
                $content = $this->convertEncoding($content, $encoding);

                $body = new \stdClass;
                $body->type = "text";
                $body->content = $content;

                $this->bodies['text'] = $body;

                $this->fetchAttachment($structure, $partNumber);

            } elseif ($structure->subtype == "HTML") {
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->connection, $this->uid, $partNumber, $this->fetch_options);
                $content = $this->decodeString($content, $structure->encoding);
                $content = $this->convertEncoding($content, $encoding);

                $body = new \stdClass;
                $body->type = "html";
                $body->content = $content;

                $this->bodies['html'] = $body;
            }
        } elseif ($structure->type == self::TYPE_MULTIPART) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber . ".";
                }
                $this->fetchStructure($subStruct, $prefix . ($index + 1));
            }
        } else {
            $this->fetchAttachment($structure, $partNumber);
        }
    }

    /**
     * Fetch the Message attachment
     *
     * @param object $structure
     * @param mixed  $partNumber
     */
    protected function fetchAttachment($structure, $partNumber){
        switch ($structure->type) {
            case self::TYPE_APPLICATION:
                $type = 'application';
                break;
            case self::TYPE_AUDIO:
                $type = 'audio';
                break;
            case self::TYPE_IMAGE:
                $type = 'image';
                break;
            case self::TYPE_VIDEO:
                $type = 'video';
                break;
            case self::TYPE_MODEL:
                $type = 'model';
                break;
            case self::TYPE_OTHER:
                $type = 'other';
                break;
            default:
                $type = 'other';
                break;
        }

        $content = imap_fetchbody($this->client->connection, $this->uid, ($partNumber) ? $partNumber : 1, $this->fetch_options);

        $attachment = new \stdClass;
        $attachment->type = $type;
        $attachment->content_type = $type.'/'.strtolower($structure->subtype);
        $attachment->content = $this->decodeString($content, $structure->encoding);

        $attachment->id = false;
        if (property_exists($structure, 'id')) {
            $attachment->id = str_replace(['<', '>'], '', $structure->id);
        }

        $attachment->name = false;
        if (property_exists($structure, 'dparameters')) {
            foreach ($structure->dparameters as $parameter) {
                if (strtolower($parameter->attribute) == "filename") {
                    $attachment->name = $parameter->value;
                    $attachment->disposition = property_exists($structure, 'disposition') ? $structure->disposition : null;
                    break;
                }
            }
        }

        if (!$attachment->name && property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "name") {
                    $attachment->name = $parameter->value;
                    $attachment->disposition = property_exists($structure, 'disposition') ? $structure->disposition : null;
                    break;
                }
            }
        }

        if ($attachment->type == 'image') {
            $attachment->img_src = 'data:'.$attachment->content_type.';base64,'.base64_encode($attachment->content);
        }

        if(property_exists($attachment, 'name')){
            if($attachment->name != false){
                if ($attachment->id) {
                    $this->attachments[$attachment->id] = $attachment;
                } else {
                    $this->attachments[] = $attachment;
                }
            }
        }
    }

    /**
     * Fail proof setter for $fetch_option
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchOption($option){
        if(is_long($option) == true){
            $this->fetch_options = $option;
        }elseif(is_null($option) == true){
            $config = config('imap.options.fetch', FT_UID);
            $this->fetch_options = is_long($config) ? $config : 1;
        }

        return $this;
    }

    /**
     * Decode a given string
     *
     * @param $string
     * @param $encoding
     *
     * @return string
     */
    private function decodeString($string, $encoding) {
        switch ($encoding) {
            case self::ENC_7BIT:
                return $string;
            case self::ENC_8BIT:
                return quoted_printable_decode(imap_8bit($string));
            case self::ENC_BINARY:
                return imap_binary($string);
            case self::ENC_BASE64:
                return imap_base64($string);
            case self::ENC_QUOTED_PRINTABLE:
                return quoted_printable_decode($string);
            case self::ENC_OTHER:
                return $string;
            default:
                return $string;
        }
    }

    /**
     * Convert the encoding
     *
     * @param $str
     * @param string $from
     * @param string $to
     *
     * @return mixed|string
     */
    private function convertEncoding($str, $from = "ISO-8859-2", $to = "UTF-8") {
        if (function_exists('iconv') && $from!='UTF-7' && $to!='UTF-7') {
            return iconv($from, $to.'//IGNORE', $str);
        } else {
            if (! $from) {
                return mb_convert_encoding($str, $to);
            }
            return mb_convert_encoding($str, $to, $from);
        }
    }

    /**
     * Get the encoding of a given abject
     *
     * @param object $structure
     *
     * @return null|string
     */
    private function getEncoding($structure) {
        if (property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "charset") {
                    return strtoupper($parameter->value);
                }
            }
        }
        return null;
    }

    /**
     * Move the Message into an other Folder
     *
     * @param string $mailbox
     *
     * @return bool
     */
    public function moveToFolder($mailbox = 'INBOX'){
        $this->client->createFolder($mailbox);

        if(imap_mail_move($this->client->getConnection(), $this->msglist, $mailbox) == true){
            return true;
        }
        return false;
    }

    /**
     * Delete the current Message
     *
     * @return bool
     */
    public function delete(){
        $status = imap_delete($this->client->connection, $this->uid, $this->fetch_options);
        $this->client->expunge();

        return $status;
    }

    /**
     * Restore a deleted Message
     *
     * @return bool
     */
    public function restore(){
        return imap_undelete($this->client->connection, $this->message_no);
    }

    /**
     * Get all message attachments.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttachments(){
        return collect($this->attachments);
    }

    /**
     * @return Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getUid() {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getFetchOptions() {
        return $this->fetch_options;
    }

    /**
     * @return int
     */
    public function getMsglist() {
        return $this->msglist;
    }

    /**
     * @return mixed
     */
    public function getMessageId() {
        return $this->message_id;
    }

    /**
     * @return int
     */
    public function getMessageNo() {
        return $this->message_no;
    }

    /**
     * @return string
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @return null
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @return array
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * @return array
     */
    public function getTo() {
        return $this->to;
    }

    /**
     * @return array
     */
    public function getCc() {
        return $this->cc;
    }

    /**
     * @return array
     */
    public function getBcc() {
        return $this->bcc;
    }

    /**
     * @return array
     */
    public function getReplyTo() {
        return $this->reply_to;
    }

    /**
     * @return array
     */
    public function getSender() {
        return $this->sender;
    }

    /**
     * @return mixed
     */
    public function getBodies() {
        return $this->bodies;
    }
}
