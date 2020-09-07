<?php
/*
* File:     Message.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Webklex\IMAP\Events\MessageDeletedEvent;
use Webklex\IMAP\Events\MessageMovedEvent;
use Webklex\IMAP\Events\MessageRestoredEvent;
use Webklex\IMAP\Exceptions\InvalidMessageDateException;
use Webklex\IMAP\Exceptions\MaskNotFoundException;
use Webklex\IMAP\Exceptions\MethodNotFoundException;
use Webklex\IMAP\Support\AttachmentCollection;
use Webklex\IMAP\Support\FlagCollection;
use Webklex\IMAP\Support\Masks\MessageMask;

/**
 * Class Message
 *
 * @package Webklex\IMAP
 *
 * @property integer msglist
 * @property integer uid
 * @property integer msgn
 * @property integer priority
 * @property string subject
 * @property string message_id
 * @property string message_no
 * @property string references
 * @property carbon date
 * @property array from
 * @property array to
 * @property array cc
 * @property array bcc
 * @property array reply_to
 * @property array in_reply_to
 * @property array sender
 * @property string fallback_encoding
 *
 * @method integer getMsglist()
 * @method integer setMsglist(integer $msglist)
 * @method integer getUid()
 * @method integer setUid(integer $uid)
 * @method integer getMsgn()
 * @method integer setMsgn(integer $msgn)
 * @method integer getPriority()
 * @method integer setPriority(integer $priority)
 * @method string getSubject()
 * @method string setSubject(string $subject)
 * @method string getMessageId()
 * @method string setMessageId(string $message_id)
 * @method string getMessageNo()
 * @method string setMessageNo(string $message_no)
 * @method string getReferences()
 * @method string setReferences(string $references)
 * @method carbon getDate()
 * @method carbon setDate(carbon $date)
 * @method array getFrom()
 * @method array setFrom(array $from)
 * @method array getTo()
 * @method array setTo(array $to)
 * @method array getCc()
 * @method array setCc(array $cc)
 * @method array getBcc()
 * @method array setBcc(array $bcc)
 * @method array getReplyTo()
 * @method array setReplyTo(array $reply_to)
 * @method array getInReplyTo()
 * @method array setInReplyTo(array $in_reply_to)
 * @method array getSender()
 * @method array setSender(array $sender)
 */
class Message {

    /**
     * Client instance
     *
     * @var Client
     */
    private $client = Client::class;

    /**
     * Default mask
     * @var string $mask
     */
    protected $mask = MessageMask::class;

    /** @var array $config */
    protected $config = [];

    /** @var array $attributes */
    protected $attributes = [
        'message_id' => '',
        'message_no' => null,
        'subject' => '',
        'references' => null,
        'date' => null,
        'from' => [],
        'to' => [],
        'cc' => [],
        'bcc' => [],
        'reply_to' => [],
        'in_reply_to' => '',
        'sender' => [],
        'priority' => 0,
    ];

    /**
     * The message folder path
     *
     * @var string $folder_path
     */
    protected $folder_path;

    /**
     * Fetch body options
     *
     * @var integer
     */
    public $fetch_options = null;

    /**
     * Fetch body options
     *
     * @var bool
     */
    public $fetch_body = null;

    /**
     * Fetch attachments options
     *
     * @var bool
     */
    public $fetch_attachment = null;

    /**
     * Fetch flags options
     *
     * @var bool
     */
    public $fetch_flags = null;

    /**
     * @var string $header
     */
    public $header = null;

    /**
     * @var null|object $header_info
     */
    public $header_info = null;

    /** @var null|string $raw_body */
    public $raw_body = null;

    /** @var null $structure */
    protected $structure = null;

    /**
     * Message body components
     *
     * @var array   $bodies
     * @var AttachmentCollection|array $attachments
     * @var FlagCollection|array       $flags
     */
    public $bodies = [];
    public $attachments = [];
    public $flags = [];

    /**
     * Fallback Encoding
     * @var string
     */
    public $fallback_encoding = 'UTF-8';

    /**
     * A list of all available and supported flags
     *
     * @var array $available_flags
     */
    private $available_flags = ['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'];

    /**
     * Message constructor.
     *
     * @param integer       $uid
     * @param integer|null  $msglist
     * @param Client        $client
     * @param integer|null  $fetch_options
     * @param boolean       $fetch_body
     * @param boolean       $fetch_attachment
     * @param boolean       $fetch_flags
     *
     * @throws Exceptions\ConnectionFailedException
     * @throws InvalidMessageDateException
     */
    public function __construct($uid, $msglist, Client $client, $fetch_options = null, $fetch_body = null, $fetch_attachment = null, $fetch_flags = null) {

        $default_mask = $client->getDefaultMessageMask();
        if($default_mask != null) {
            $this->mask = $default_mask;
        }

        $this->folder_path = $client->getFolderPath();

        $this->config = config('imap.options');

        $this->setFetchOption($fetch_options);
        $this->setFetchBodyOption($fetch_body);
        $this->setFetchAttachmentOption($fetch_attachment);
        $this->setFetchFlagsOption($fetch_flags);

        $this->attachments = AttachmentCollection::make([]);
        $this->flags = FlagCollection::make([]);

        $this->msglist = $msglist;
        $this->client = $client;

        $this->uid =  $uid;
        $this->msgn = ($this->fetch_options == IMAP::FT_UID) ? \imap_msgno($this->client->getConnection(), $uid) : $uid;

        $this->parseHeader();

        if ($this->getFetchFlagsOption() === true) {
            $this->parseFlags();
        }

        if ($this->getFetchBodyOption() === true) {
            $this->parseBody();
        }
    }

    /**
     * Call dynamic attribute setter and getter methods
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws MethodNotFoundException
     */
    public function __call($method, $arguments) {
        if(strtolower(substr($method, 0, 3)) === 'get') {
            $name = Str::snake(substr($method, 3));

            if(in_array($name, array_keys($this->attributes))) {
                return $this->attributes[$name];
            }

        }elseif (strtolower(substr($method, 0, 3)) === 'set') {
            $name = Str::snake(substr($method, 3));

            if(in_array($name, array_keys($this->attributes))) {
                $this->attributes[$name] = array_pop($arguments);

                return $this->attributes[$name];
            }

        }

        throw new MethodNotFoundException("Method ".self::class.'::'.$method.'() is not supported');
    }

    /**
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function __set($name, $value) {
        $this->attributes[$name] = $value;

        return $this->attributes[$name];
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name) {
        if(isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Copy the current Messages to a mailbox
     *
     * @param $mailbox
     * @param int $options
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     */
    public function copy($mailbox, $options = 0) {
        $this->client->openFolder($this->folder_path);
        return \imap_mail_copy($this->client->getConnection(), $this->uid, $mailbox, IMAP::CP_UID);
    }

    /**
     * Move the current Messages to a mailbox
     *
     * @param $mailbox
     * @param int $options
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     */
    public function move($mailbox, $options = 0) {
        $this->client->openFolder($this->folder_path);
        return \imap_mail_move($this->client->getConnection(), $this->uid, $mailbox, IMAP::CP_UID);
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
     * @return string|null
     */
    public function getHTMLBody() {
        if (!isset($this->bodies['html'])) {
            return null;
        }
        return $this->bodies['html']->content;
    }

    /**
     * Parse all defined headers
     *
     * @return void
     * @throws Exceptions\ConnectionFailedException
     * @throws InvalidMessageDateException
     */
    private function parseHeader() {
        $this->client->openFolder($this->folder_path);
        $this->header = $header = \imap_fetchheader($this->client->getConnection(), $this->uid, IMAP::FT_UID);

        $this->priority = $this->extractPriority($this->header);

        if ($this->header) {
            $header = \imap_rfc822_parse_headers($this->header);
        }

        if (property_exists($header, 'subject')) {
            if($this->config['decoder']['message']['subject'] === 'utf-8') {
                $this->subject = \imap_utf8($header->subject);
            }elseif($this->config['decoder']['message']['subject'] === 'iconv') {
                $this->subject = iconv_mime_decode($header->subject);
            }else{
                $this->subject = mb_decode_mimeheader($header->subject);
            }
        }

        foreach(['from', 'to', 'cc', 'bcc', 'reply_to', 'sender'] as $part){
            $this->extractHeaderAddressPart($header, $part);
        }

        if (property_exists($header, 'references')) {
            $this->references = $header->references;
        }
        if (property_exists($header, 'in_reply_to')) {
            $this->in_reply_to = str_replace(['<', '>'], '', $header->in_reply_to);
        }
        if (property_exists($header, 'message_id')) {
            $this->message_id = str_replace(['<', '>'], '', $header->message_id);
        }
        if (property_exists($header, 'Msgno')) {
            $messageNo = (int) trim($header->Msgno);
            $this->message_no = ($this->fetch_options == IMAP::FT_UID) ? $messageNo : \imap_msgno($this->client->getConnection(), $messageNo);
        } else {
            $this->message_no = \imap_msgno($this->client->getConnection(), $this->getUid());
        }

        $this->date = $this->parseDate($header);
    }

    /**
     * Try to extract the priority from a given raw header string
     * @param string $header
     *
     * @return int|null
     */
    private function extractPriority($header) {
        if(preg_match('/x\-priority\:.*([0-9]{1,2})/i', $header, $priority)){
            $priority = isset($priority[1]) ? (int) $priority[1] : 0;
            switch($priority){
                case IMAP::MESSAGE_PRIORITY_HIGHEST;
                    $priority = IMAP::MESSAGE_PRIORITY_HIGHEST;
                    break;
                case IMAP::MESSAGE_PRIORITY_HIGH;
                    $priority = IMAP::MESSAGE_PRIORITY_HIGH;
                    break;
                case IMAP::MESSAGE_PRIORITY_NORMAL;
                    $priority = IMAP::MESSAGE_PRIORITY_NORMAL;
                    break;
                case IMAP::MESSAGE_PRIORITY_LOW;
                    $priority = IMAP::MESSAGE_PRIORITY_LOW;
                    break;
                case IMAP::MESSAGE_PRIORITY_LOWEST;
                    $priority = IMAP::MESSAGE_PRIORITY_LOWEST;
                    break;
                default:
                    $priority = IMAP::MESSAGE_PRIORITY_UNKNOWN;
                    break;
            }
        }

        return $priority;
    }

    /**
     * Exception handling for invalid dates
     *
     * Currently known invalid formats:
     * ^ Datetime                                   ^ Problem                           ^ Cause
     * | Mon, 20 Nov 2017 20:31:31 +0800 (GMT+8:00) | Double timezone specification     | A Windows feature
     * | Thu, 8 Nov 2018 08:54:58 -0200 (-02)       |
     * |                                            | and invalid timezone (max 6 char) |
     * | 04 Jan 2018 10:12:47 UT                    | Missing letter "C"                | Unknown
     * | Thu, 31 May 2018 18:15:00 +0800 (added by) | Non-standard details added by the | Unknown
     * |                                            | mail server                       |
     * | Sat, 31 Aug 2013 20:08:23 +0580            | Invalid timezone                  | PHPMailer bug https://sourceforge.net/p/phpmailer/mailman/message/6132703/
     *
     * Please report any new invalid timestamps to [#45](https://github.com/Webklex/laravel-imap/issues/45)
     *
     * @param object $header
     *
     * @return Carbon|null
     * @throws InvalidMessageDateException
     */
    private function parseDate($header) {
        $parsed_date = null;

        if (property_exists($header, 'date')) {
            $date = $header->date;

            if(preg_match('/\+0580/', $date)) {
                $date = str_replace('+0580', '+0530', $date);
            }

            $date = trim(rtrim($date));
            try {
                $parsed_date = Carbon::parse($date);
            } catch (\Exception $e) {
                switch (true) {
                    case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                        $date .= 'C';
                        break;
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ \+[0-9]{2,4}\ \(\+[0-9]{1,2}\))+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}[\,|\ \,]\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}.*)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\, \ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                    case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{2,4}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}\ [A-Z]{2}\ \-[0-9]{2}\:[0-9]{2}\ \([A-Z]{2,3}\ \-[0-9]{2}:[0-9]{2}\))+$/i', $date) > 0:
                        $array = explode('(', $date);
                        $array = array_reverse($array);
                        $date = trim(array_pop($array));
                        break;
                }
                try{
                    $parsed_date = Carbon::parse($date);
                } catch (\Exception $_e) {
                    throw new InvalidMessageDateException("Invalid message date. ID:".$this->getMessageId(), 1100, $e);
                }
            }
        }

        return $parsed_date;
    }

    /**
     * Parse additional flags
     *
     * @return void
     * @throws Exceptions\ConnectionFailedException
     */
    private function parseFlags() {
        $this->flags = FlagCollection::make([]);

        $this->client->openFolder($this->folder_path);
        $flags = \imap_fetch_overview($this->client->getConnection(), $this->uid, IMAP::FT_UID);
        if (is_array($flags) && isset($flags[0])) {
            foreach($this->available_flags as $flag) {
                $this->parseFlag($flags, $flag);
            }
        }
    }

    /**
     * Extract a possible flag information from a given array
     * @param array $flags
     * @param string $flag
     */
    private function parseFlag($flags, $flag) {
        $flag = strtolower($flag);

        if (property_exists($flags[0], strtoupper($flag))) {
            $this->flags->put($flag, $flags[0]->{strtoupper($flag)});
        } elseif (property_exists($flags[0], ucfirst($flag))) {
            $this->flags->put($flag, $flags[0]->{ucfirst($flag)});
        } elseif (property_exists($flags[0], $flag)) {
            $this->flags->put($flag, $flags[0]->$flag);
        }
    }

    /**
     * Get the current Message header info
     *
     * @return object
     * @throws Exceptions\ConnectionFailedException
     */
    public function getHeaderInfo() {
        if ($this->header_info == null) {
            $this->client->openFolder($this->folder_path);
            $this->header_info = \imap_headerinfo($this->client->getConnection(), $this->getMessageNo());
        }

        return $this->header_info;
    }

    /**
     * Extract a given part as address array from a given header
     * @param object $header
     * @param string $part
     */
    private function extractHeaderAddressPart($header, $part) {
        if (property_exists($header, $part)) {
            $this->$part = $this->parseAddresses($header->$part);
        }
    }

    /**
     * Parse Addresses
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
            } else {
                $personalParts = \imap_mime_header_decode($address->personal);

                if(is_array($personalParts)) {
                    $address->personal = '';
                    foreach ($personalParts as $p) {
                        $address->personal .= $this->convertEncoding($p->text, $this->getEncoding($p));
                    }
                }
            }

            $address->mail = ($address->mailbox && $address->host) ? $address->mailbox.'@'.$address->host : false;
            $address->full = ($address->personal) ? $address->personal.' <'.$address->mail.'>' : $address->mail;

            $addresses[] = $address;
        }

        return $addresses;
    }

    /**
     * Parse the Message body
     *
     * @return $this
     * @throws Exceptions\ConnectionFailedException
     */
    public function parseBody() {
        $this->client->openFolder($this->folder_path);

        $this->structure = \imap_fetchstructure($this->client->getConnection(), $this->uid, IMAP::FT_UID);
        $this->fetchStructure($this->structure);

        return $this;
    }

    /**
     * Fetch the Message structure
     *
     * @param $structure
     * @param mixed $partNumber
     *
     * @throws Exceptions\ConnectionFailedException
     */
    private function fetchStructure($structure, $partNumber = null) {
        $this->client->openFolder($this->folder_path);

        if ($structure->type == IMAP::MESSAGE_TYPE_TEXT &&
            (empty($structure->disposition) || strtolower($structure->disposition) != 'attachment')
        ) {
            if (strtolower($structure->subtype) == "plain" || strtolower($structure->subtype) == "csv") {
                $this->bodies['text'] = $this->createBody("text", $structure, $partNumber);
                $this->fetchAttachment($structure, $partNumber);
            } elseif (strtolower($structure->subtype) == "html") {
                $this->bodies['html'] = $this->createBody("html", $structure, $partNumber);
            } elseif ($structure->ifdisposition == 1 && strtolower($structure->disposition) == 'attachment') {
                if ($this->getFetchAttachmentOption() === true) {
                    $this->fetchAttachment($structure, $partNumber);
                }
            }
        } elseif ($structure->type == IMAP::MESSAGE_TYPE_MULTIPART) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber.".";
                }
                $this->fetchStructure($subStruct, $prefix.($index + 1));
            }
        } else if ($this->getFetchAttachmentOption() === true) {
            $this->fetchAttachment($structure, $partNumber);
        }
    }

    /**
     * Create a new body object of a given type
     * @param string $type
     * @param object $structure
     * @param mixed $partNumber
     *
     * @return object
     * @throws Exceptions\ConnectionFailedException
     */
    private function createBody($type, $structure, $partNumber){
        return (object) [
            "type" => $type,
            "content" => $this->fetchPart($structure, $partNumber),
        ];
    }

    /**
     * Fetch the content of a given part and message structure
     * @param object $structure
     * @param mixed $partNumber
     *
     * @return mixed|string
     * @throws Exceptions\ConnectionFailedException
     */
    private function fetchPart($structure, $partNumber){
        $encoding = $this->getEncoding($structure);

        if (!$partNumber) {
            $partNumber = 1;
        }

        $content = \imap_fetchbody($this->client->getConnection(), $this->uid, $partNumber, $this->fetch_options | IMAP::FT_UID);
        $content = $this->decodeString($content, $structure->encoding);

        // We don't need to do convertEncoding() if charset is ASCII (us-ascii):
        //     ASCII is a subset of UTF-8, so all ASCII files are already UTF-8 encoded
        //     https://stackoverflow.com/a/11303410
        //
        // us-ascii is the same as ASCII:
        //     ASCII is the traditional name for the encoding system; the Internet Assigned Numbers Authority (IANA)
        //     prefers the updated name US-ASCII, which clarifies that this system was developed in the US and
        //     based on the typographical symbols predominantly in use there.
        //     https://en.wikipedia.org/wiki/ASCII
        //
        // convertEncoding() function basically means convertToUtf8(), so when we convert ASCII string into UTF-8 it gets broken.
        if ($encoding != 'us-ascii') {
            $content = $this->convertEncoding($content, $encoding);
        }

        return $content;
    }

    /**
     * Fetch the Message attachment
     *
     * @param object $structure
     * @param mixed  $partNumber
     *
     * @throws Exceptions\ConnectionFailedException
     */
    protected function fetchAttachment($structure, $partNumber) {

        $oAttachment = new Attachment($this, $structure, $partNumber);

        if ($oAttachment->getName() !== null) {
            if ($oAttachment->getId() !== null) {
                $this->attachments->put($oAttachment->getId(), $oAttachment);
            } else {
                $this->attachments->push($oAttachment);
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
    public function setFetchOption($option) {
        if (is_long($option) === true) {
            $this->fetch_options = $option;
        } elseif (is_null($option) === true) {
            $config = config('imap.options.fetch', IMAP::FT_UID);
            $this->fetch_options = is_long($config) ? $config : 1;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_body
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchBodyOption($option) {
        if (is_bool($option)) {
            $this->fetch_body = $option;
        } elseif (is_null($option)) {
            $config = config('imap.options.fetch_body', true);
            $this->fetch_body = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_attachment
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchAttachmentOption($option) {
        if (is_bool($option)) {
            $this->fetch_attachment = $option;
        } elseif (is_null($option)) {
            $config = config('imap.options.fetch_attachment', true);
            $this->fetch_attachment = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_flags
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchFlagsOption($option) {
        if (is_bool($option)) {
            $this->fetch_flags = $option;
        } elseif (is_null($option)) {
            $config = config('imap.options.fetch_flags', true);
            $this->fetch_flags = is_bool($config) ? $config : true;
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
    public function decodeString($string, $encoding) {
        switch ($encoding) {
            case IMAP::MESSAGE_ENC_8BIT:
                return quoted_printable_decode(\imap_8bit($string));
            case IMAP::MESSAGE_ENC_BINARY:
                return \imap_binary($string);
            case IMAP::MESSAGE_ENC_BASE64:
                return \imap_base64($string);
            case IMAP::MESSAGE_ENC_QUOTED_PRINTABLE:
                return quoted_printable_decode($string);
            case IMAP::MESSAGE_ENC_7BIT:
            case IMAP::MESSAGE_ENC_OTHER:
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
    public function convertEncoding($str, $from = "ISO-8859-2", $to = "UTF-8") {

        $from = EncodingAliases::get($from, $this->fallback_encoding);
        $to = EncodingAliases::get($to, $this->fallback_encoding);

        if ($from === $to) {
            return $str;
        }

        // We don't need to do convertEncoding() if charset is ASCII (us-ascii):
        //     ASCII is a subset of UTF-8, so all ASCII files are already UTF-8 encoded
        //     https://stackoverflow.com/a/11303410
        //
        // us-ascii is the same as ASCII:
        //     ASCII is the traditional name for the encoding system; the Internet Assigned Numbers Authority (IANA)
        //     prefers the updated name US-ASCII, which clarifies that this system was developed in the US and
        //     based on the typographical symbols predominantly in use there.
        //     https://en.wikipedia.org/wiki/ASCII
        //
        // convertEncoding() function basically means convertToUtf8(), so when we convert ASCII string into UTF-8 it gets broken.
        if (strtolower($from) == 'us-ascii' && $to == 'UTF-8') {
            return $str;
        }

        if (function_exists('iconv') && $from != 'UTF-7' && $to != 'UTF-7') {
            return @iconv($from, $to.'//IGNORE', $str);
        } else {
            if (!$from) {
                return mb_convert_encoding($str, $to);
            }
            return mb_convert_encoding($str, $to, $from);
        }
    }

    /**
     * Get the encoding of a given abject
     *
     * @param object|string $structure
     *
     * @return string
     */
    public function getEncoding($structure) {
        if (property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "charset") {
                    return EncodingAliases::get($parameter->value, $this->fallback_encoding);
                }
            }
        }elseif (property_exists($structure, 'charset')) {
            return EncodingAliases::get($structure->charset, $this->fallback_encoding);
        }elseif (is_string($structure) === true){
            return mb_detect_encoding($structure);
        }

        return $this->fallback_encoding;
    }

    /**
     * Find the folder containing this message.
     * @param null|Folder $folder where to start searching from (top-level inbox by default)
     *
     * @return mixed|null|Folder
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\MailboxFetchingException
     * @throws InvalidMessageDateException
     * @throws MaskNotFoundException
     */
    public function getContainingFolder(Folder $folder = null) {
        $folder = $folder ?: $this->client->getFolders()->first();
        $this->client->checkConnection();

        // Try finding the message by uid in the current folder
        $client = new Client;
        $client->openFolder($folder->path);
        $uidMatches = \imap_fetch_overview($client->getConnection(), $this->uid, IMAP::FT_UID);
        $uidMatch = count($uidMatches)
            ? new Message($uidMatches[0]->uid, $uidMatches[0]->msgno, $client)
            : null;
        $client->disconnect();

        // \imap_fetch_overview() on a parent folder will return the matching message
        // even when the message is in a child folder so we need to recursively
        // search the children
        foreach ($folder->children as $child) {
            $childFolder = $this->getContainingFolder($child);

            if ($childFolder) {
                return $childFolder;
            }
        }

        // before returning the parent
        if ($this->is($uidMatch)) {
            return $folder;
        }

        // or signalling that the message was not found in any folder
        return null;
    }

    public function getFolder(){
        return $this->client->getFolder($this->folder_path);
    }

    /**
     * Move the Message into an other Folder
     * @param string $mailbox
     * @param bool $expunge
     * @param bool $create_folder
     *
     * @return null|Message
     * @throws Exceptions\ConnectionFailedException
     * @throws InvalidMessageDateException
     */
    public function moveToFolder($mailbox = 'INBOX', $expunge = false, $create_folder = true) {

        if($create_folder) $this->client->createFolder($mailbox, true);

        $target_folder = $this->client->getFolder($mailbox);
        $target_status = $target_folder->getStatus(IMAP::SA_ALL);

        $this->client->openFolder($this->folder_path);
        $status = \imap_mail_move($this->client->getConnection(), $this->uid, $mailbox, IMAP::CP_UID);

        if($status === true){
            if($expunge) $this->client->expunge();
            $this->client->openFolder($target_folder->path);

            $message = $target_folder->getMessage($target_status->uidnext, null, $this->fetch_options, $this->fetch_body, $this->fetch_attachment, $this->fetch_flags);
            MessageMovedEvent::dispatch($this, $message);
            return $message;
        }

        return null;
    }

    /**
     * Delete the current Message
     * @param bool $expunge
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     */
    public function delete($expunge = true) {
        $this->client->openFolder($this->folder_path);

        $status = \imap_delete($this->client->getConnection(), $this->uid, IMAP::FT_UID);
        if($expunge) $this->client->expunge();
        MessageDeletedEvent::dispatch($this);

        return $status;
    }

    /**
     * Restore a deleted Message
     * @param boolean $expunge
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     */
    public function restore($expunge = true) {
        $this->client->openFolder($this->folder_path);

        $status = \imap_undelete($this->client->getConnection(), $this->uid, IMAP::FT_UID);
        if($expunge) $this->client->expunge();
        MessageRestoredEvent::dispatch($this);

        return $status;
    }

    /**
     * Get all message attachments.
     *
     * @return AttachmentCollection
     */
    public function getAttachments() {
        return $this->attachments;
    }

    /**
     * Checks if there are any attachments present
     *
     * @return boolean
     */
    public function hasAttachments() {
        return $this->attachments->isEmpty() === false;
    }

    /**
     * Set a given flag
     * @param string|array $flag
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     */
    public function setFlag($flag) {
        $this->client->openFolder($this->folder_path);

        $flag = "\\".trim(is_array($flag) ? implode(" \\", $flag) : $flag);
        $status = \imap_setflag_full($this->client->getConnection(), $this->getUid(), $flag, SE_UID);
        $this->parseFlags();

        return $status;
    }

    /**
     * Unset a given flag
     * @param string|array $flag
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     */
    public function unsetFlag($flag) {
        $this->client->openFolder($this->folder_path);

        $flag = "\\".trim(is_array($flag) ? implode(" \\", $flag) : $flag);
        $status = \imap_clearflag_full($this->client->getConnection(), $this->getUid(), $flag, SE_UID);
        $this->parseFlags();

        return $status;
    }

    /**
     * @return null|object|string
     * @throws Exceptions\ConnectionFailedException
     */
    public function getRawBody() {
        if ($this->raw_body === null) {
            $this->client->openFolder($this->folder_path);

            $this->raw_body = \imap_fetchbody($this->client->getConnection(), $this->getUid(), '', $this->fetch_options | IMAP::FT_UID);
        }

        return $this->raw_body;
    }

    /**
     * Get an almost unique message token
     * @return string
     * @throws Exceptions\ConnectionFailedException
     */
    public function getToken(){
        return base64_encode(implode('-', [$this->message_id, $this->subject, strlen($this->getRawBody())]));
    }

    /**
     * @return string
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * @return Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * @return integer
     */
    public function getFetchOptions() {
        return $this->fetch_options;
    }

    /**
     * @return boolean
     */
    public function getFetchBodyOption() {
        return $this->fetch_body;
    }

    /**
     * @return boolean
     */
    public function getFetchAttachmentOption() {
        return $this->fetch_attachment;
    }

    /**
     * @return boolean
     */
    public function getFetchFlagsOption() {
        return $this->fetch_flags;
    }

    /**
     * @return mixed
     */
    public function getBodies() {
        return $this->bodies;
    }

    /**
     * @return FlagCollection
     */
    public function getFlags() {
        return $this->flags;
    }

    /**
     * @return object|null
     */
    public function getStructure(){
        return $this->structure;
    }

    /**
     * Does this message match another one?
     *
     * A match means same uid, message id, subject, body length and date/time.
     *
     * @param  null|Message $message
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     */
    public function is(Message $message = null) {
        if (is_null($message)) {
            return false;
        }

        return $this->getToken() == $message->getToken() && $this->date->eq($message->date);
    }

    /**
     * @return array
     */
    public function getAttributes(){
        return $this->attributes;
    }

    /**
     * @param $mask
     * @return $this
     */
    public function setMask($mask){
        if(class_exists($mask)){
            $this->mask = $mask;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMask(){
        return $this->mask;
    }

    /**
     * Get a masked instance by providing a mask name
     * @param string|null $mask
     *
     * @return mixed
     * @throws MaskNotFoundException
     */
    public function mask($mask = null){
        $mask = $mask !== null ? $mask : $this->mask;
        if(class_exists($mask)){
            return new $mask($this);
        }

        throw new MaskNotFoundException("Unknown mask provided: ".$mask);
    }
}
