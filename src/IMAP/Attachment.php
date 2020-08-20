<?php
/*
* File:     Attachment.php
* Category: -
* Author:   M. Goldenbaum
* Created:  16.03.18 19:37
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Webklex\IMAP\Exceptions\MaskNotFoundException;
use Webklex\IMAP\Exceptions\MethodNotFoundException;
use Webklex\IMAP\Support\Masks\AttachmentMask;

/**
 * Class Attachment
 *
 * @package Webklex\IMAP
 * 
 * @property integer part_number
 * @property integer size
 * @property string content
 * @property string type
 * @property string content_type
 * @property string id
 * @property string name
 * @property string disposition
 * @property string img_src
 *
 * @method integer getPartNumber()
 * @method integer setPartNumber(integer $part_number)
 * @method string  getContent()
 * @method string  setContent(string $content)
 * @method string  getType()
 * @method string  setType(string $type)
 * @method string  getContentType()
 * @method string  setContentType(string $content_type)
 * @method string  getId()
 * @method string  setId(string $id)
 * @method string  getSize()
 * @method string  setSize(integer $size)
 * @method string  getName()
 * @method string  getDisposition()
 * @method string  setDisposition(string $disposition)
 * @method string  setImgSrc(string $img_src)
 */
class Attachment {

    /** @var Message $oMessage */
    protected $oMessage;

    /** @var array $config */
    protected $config = [];

    /** @var object $structure */
    protected $structure;
    
    /** @var array $attributes */
    protected $attributes = [
        'part_number' => 1,
        'content' => null,
        'type' => null,
        'content_type' => null,
        'id' => null,
        'name' => null,
        'disposition' => null,
        'img_src' => null,
        'size' => null,
    ];

    /**
     * Default mask
     * @var string $mask
     */
    protected $mask = AttachmentMask::class;

    /**
     * Attachment constructor.
     *
     * @param Message   $oMessage
     * @param object    $structure
     * @param integer   $part_number
     *
     * @throws Exceptions\ConnectionFailedException
     */
    public function __construct(Message $oMessage, $structure, $part_number = 1) {
        $this->config = config('imap.options');

        $this->oMessage = $oMessage;
        $this->structure = $structure;
        $this->part_number = ($part_number) ? $part_number : $this->part_number;

        $default_mask = $this->oMessage->getClient()->getDefaultAttachmentMask();
        if($default_mask != null) {
            $this->mask = $default_mask;
        }

        $this->findType();
        $this->fetch();
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

            if(isset($this->attributes[$name])) {
                return $this->attributes[$name];
            }

            return null;
        }elseif (strtolower(substr($method, 0, 3)) === 'set') {
            $name = Str::snake(substr($method, 3));

            $this->attributes[$name] = array_pop($arguments);

            return $this->attributes[$name];
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
     * Determine the structure type
     */
    protected function findType() {
        switch ($this->structure->type) {
            case IMAP::ATTACHMENT_TYPE_MESSAGE:
                $this->type = 'message';
                break;
            case IMAP::ATTACHMENT_TYPE_APPLICATION:
                $this->type = 'application';
                break;
            case IMAP::ATTACHMENT_TYPE_AUDIO:
                $this->type = 'audio';
                break;
            case IMAP::ATTACHMENT_TYPE_IMAGE:
                $this->type = 'image';
                break;
            case IMAP::ATTACHMENT_TYPE_VIDEO:
                $this->type = 'video';
                break;
            case IMAP::ATTACHMENT_TYPE_MODEL:
                $this->type = 'model';
                break;
            case IMAP::ATTACHMENT_TYPE_TEXT:
                $this->type = 'text';
                break;
            case IMAP::ATTACHMENT_TYPE_MULTIPART:
                $this->type = 'multipart';
                break;
            default:
                $this->type = 'other';
                break;
        }
    }

    /**
     * Fetch the given attachment
     *
     * @throws Exceptions\ConnectionFailedException
     */
    protected function fetch() {

        $content = \imap_fetchbody($this->oMessage->getClient()->getConnection(), $this->oMessage->getUid(), $this->part_number, $this->oMessage->getFetchOptions() | FT_UID);

        $this->content_type = $this->type.'/'.strtolower($this->structure->subtype);
        $this->content = $this->oMessage->decodeString($content, $this->structure->encoding);

        if (property_exists($this->structure, 'id')) {
            $this->id = str_replace(['<', '>'], '', $this->structure->id);
        }

        if (property_exists($this->structure, 'bytes')) {
            $this->size = $this->structure->bytes;
        }

        if (property_exists($this->structure, 'dparameters')) {
            foreach ($this->structure->dparameters as $parameter) {
                if (strtolower($parameter->attribute) == "filename") {
                    $this->setName($parameter->value);
                    $this->disposition = property_exists($this->structure, 'disposition') ? $this->structure->disposition : null;
                    break;
                }
            }
        }

        if (IMAP::ATTACHMENT_TYPE_MESSAGE == $this->structure->type) {
            if ($this->structure->ifdescription) {
                $this->setName($this->structure->description);
            } else {
                $this->setName($this->structure->subtype);
            }
        }

        if (!$this->name && property_exists($this->structure, 'parameters')) {
            foreach ($this->structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "name") {
                    $this->setName($parameter->value);
                    $this->disposition = property_exists($this->structure, 'disposition') ? $this->structure->disposition : null;
                    break;
                }
            }
        }
    }

    /**
     * Save the attachment content to your filesystem
     *
     * @param string|null $path
     * @param string|null $filename
     *
     * @return boolean
     */
    public function save($path = null, $filename = null) {
        $path = $path ?: storage_path();
        $filename = $filename ?: $this->getName();

        $path = substr($path, -1) == DIRECTORY_SEPARATOR ? $path : $path.DIRECTORY_SEPARATOR;

        return File::put($path.$filename, $this->getContent()) !== false;
    }

    /**
     * @param $name
     */
    public function setName($name) {
        if($this->config['decoder']['attachment']['name'] === 'utf-8') {
            $this->name = \imap_utf8($name);
        }elseif($this->config['decoder']['attachment']['name'] === 'iconv') {
            $this->name = iconv_mime_decode($name);
        }else{
            $this->name = mb_decode_mimeheader($name);
        }
    }

    /**
     * @return null|string
     *
     * @deprecated 1.4.0:2.0.0 No longer needed. Use AttachmentMask::getImageSrc() instead
     */
    public function getImgSrc() {
        if ($this->type == 'image' && $this->img_src == null) {
            $this->img_src = 'data:'.$this->content_type.';base64,'.base64_encode($this->content);
        }
        return $this->img_src;
    }

    /**
     * @return string|null
     */
    public function getMimeType(){
        return (new \finfo())->buffer($this->getContent(), FILEINFO_MIME_TYPE);
    }

    /**
     * @return string|null
     */
    public function getExtension(){
        $deprecated_guesser = "\Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser";
        if (class_exists($deprecated_guesser) !== false){
            return $deprecated_guesser::getInstance()->guess($this->getMimeType());
        }
        $guesser = "\Symfony\Component\Mime\MimeTypes";
        $extensions = $guesser::getDefault()->getExtensions($this->getMimeType());
        return isset($extensions[0]) ? $extensions[0] : null;
    }

    /**
     * @return array
     */
    public function getAttributes(){
        return $this->attributes;
    }

    /**
     * @return Message
     */
    public function getMessage(){
        return $this->oMessage;
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
