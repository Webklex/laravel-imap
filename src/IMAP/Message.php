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

class Message {


    /**
     * Client instance
     *
     * @var Client
     */
    private $client = Client::class;


    /**
     * U ID
     *
     * @var string
     */
    public $uid = '';
    public $msglist = 1;

    /* HEADER */
    public $subject = '';
    public $date = null;

    public $from = [];
    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $reply_to = [];
    public $sender = [];

    public $message_id = '';
    public $message_no = null;

    /* BODY */
    public $bodies = [];
    public $attachments = [];

    /* Consts */
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


    public function __construct($uid, $msglist, Client $client)
    {
        $this->uid = $uid;
        $this->msglist = $msglist;
        $this->client = $client;

        $this->parseHeader();
        $this->parseBody();
    }

    public function hasTextBody()
    {
        return isset($this->bodies['text']);
    }

    public function getTextBody()
    {
        if (!isset($this->bodies['text'])) {
            return false;
        }

        return $this->bodies['text']->content;
    }

    public function hasHTMLBody()
    {
        return isset($this->bodies['html']);
    }

    public function getHTMLBody($replaceImages = false)
    {
        if (!isset($this->bodies['html'])) {
            return false;
        }

        $body = $this->bodies['html']->content;
        if ($replaceImages) {
            foreach ($this->attachments as $attachment) {
                if ($attachment->id) {
                    $body = str_replace('cid:'.$attachment->id, $attachment->img_src, $body);
                }
            }
        }

        return $body;
    }

    private function parseHeader()
    {
        $header = imap_fetchheader($this->client->connection, $this->uid, FT_UID);
        if ($header) {
            $header = imap_rfc822_parse_headers($header);
        }

        if (property_exists($header, 'subject')) {
            $this->subject = imap_utf8($header->subject);
        }
        if (property_exists($header, 'date')) {
            $this->date = Carbon::parse($header->date);
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
            $this->message_no = trim($header->Msgno);
        }
    }

    private function parseAddresses($list)
    {
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

    private function parseBody()
    {
        $structure = imap_fetchstructure($this->client->connection, $this->uid, FT_UID);

        $this->fetchStructure($structure);
    }

    private function fetchStructure($structure, $partNumber = null)
    {
        if ($structure->type == self::TYPE_TEXT) {
            if ($structure->subtype == "PLAIN") {
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->connection, $this->uid, $partNumber, FT_UID);
                $content = $this->decodeString($content, $structure->encoding);
                $content = $this->convertEncoding($content, $encoding);

                $body = new \stdClass;
                $body->type = "text";
                $body->content = $content;

                $this->bodies['text'] = $body;

            } elseif ($structure->subtype == "HTML") {
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->connection, $this->uid, $partNumber, FT_UID);
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

            $content = imap_fetchbody($this->client->connection, $this->uid, ($partNumber) ? $partNumber : 1, FT_UID);

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
                    if ($parameter->attribute == "filename") {
                        $attachment->name = $parameter->value;
                        break;
                    }
                }
            }

            if (!$attachment->name && property_exists($structure, 'parameters')) {
                foreach ($structure->parameters as $parameter) {
                    if ($parameter->attribute == "name") {
                        $attachment->name = $parameter->value;
                        break;
                    }
                }
            }

            if ($attachment->type == 'image') {
                $attachment->img_src = 'data:'.$attachment->content_type.';base64,'.base64_encode($attachment->content);
            }

            if ($attachment->id) {
                $this->attachments[$attachment->id] = $attachment;
            } else {
                $this->attachments[] = $attachment;
            }
        }
    }

    private function decodeString($string, $encoding)
    {
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

    private function convertEncoding($str, $from = "ISO-8859-2", $to = "UTF-8")
    {
        if (!$from) {
            return mb_convert_encoding($str, $to);
        }
        return mb_convert_encoding($str, $to, $from);
    }

    private function getEncoding($structure)
    {
        if (property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if ($parameter->attribute == "charset") {
                    return strtoupper($parameter->value);
                }
            }
        }
        return null;
    }

    public function moveToFolder($mailbox = 'INBOX'){
        $this->client->createFolder($mailbox);

        if(imap_mail_move($this->client->connection, $this->msglist, $mailbox) == true){
            return imap_expunge($this->client->connection);
        }
        return false;
    }
}
