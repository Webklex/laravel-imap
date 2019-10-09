<?php
/*
* File: MessageMask.php
* Category: Mask
* Author: M.Goldenbaum
* Created: 14.03.19 20:49
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\IMAP\Support\Masks;

use Webklex\IMAP\Attachment;
use Webklex\IMAP\Message;

/**
 * Class MessageMask
 *
 * @package Webklex\IMAP\Support\Masks
 */
class MessageMask extends Mask {

    /** @var Message $parent */
    protected $parent;

    /**
     * Get the message html body
     * @return null
     */
    public function getHtmlBody(){
        $bodies = $this->parent->getBodies();
        if (!isset($bodies['html'])) {
            return null;
        }

        return $bodies['html']->content;
    }

    /**
     * Get the Message html body filtered by an optional callback
     * @param callable|bool $callback
     *
     * @return string|null
     */
    public function getCustomHTMLBody($callback = false) {
        $body = $this->getHtmlBody();
        if($body === null) return null;

        if ($callback !== false) {
            $aAttachment = $this->parent->getAttachments();
            $aAttachment->each(function($oAttachment) use(&$body, $callback) {
                /** @var Attachment $oAttachment */
                if(is_callable($callback)) {
                    $body = $callback($body, $oAttachment);
                }elseif(is_string($callback)) {
                    call_user_func($callback, [$body, $oAttachment]);
                }
            });
        }

        return $body;
    }

    /**
     * Get the Message html body with embedded base64 images
     * the resulting $body.
     *
     * @return string|null
     */
    public function getHTMLBodyWithEmbeddedBase64Images() {
        return $this->getCustomHTMLBody(function($body, $oAttachment){
            /** @var \Webklex\IMAP\Attachment $oAttachment */
            if ($oAttachment->id && $oAttachment->getImgSrc() != null) {
                $body = str_replace('cid:'.$oAttachment->id, $oAttachment->getImgSrc(), $body);
            }

            return $body;
        });
    }

    /**
     * Get the Message html body with embedded image urls
     * the resulting $body.
     *
     * @param string $route_name
     * @param array $params
     *
     * @return null|string
     */
    public function getHTMLBodyWithEmbeddedUrlImages($route_name, $params = []) {
        return $this->getCustomHTMLBody(function($body, $oAttachment) use($route_name, $params){
            /** @var \Webklex\IMAP\Attachment $oAttachment */
            if ($oAttachment->id && $oAttachment->getImgSrc() != null) {
                $oMessage = $oAttachment->getMessage();

                $image_url = route($route_name, array_merge([
                    'muid' => urlencode($oMessage->uid),
                    'mid' => urlencode($oMessage->message_id),
                    'mti' => urlencode($oMessage->date->timestamp),
                    'aid' => urlencode($oAttachment->id)
                ], $params));

                $body = str_replace('cid:'.$oAttachment->id, $image_url, $body);
            }

            return $body;
        });
    }
}