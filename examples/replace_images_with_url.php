<?php
/*
* File: replace_images_with_url.php
* Category: Example
* Author: M.Goldenbaum
* Created: 14.03.19 18:47
* Updated: -
*
* Description:
*  -
*/

/** @var \Webklex\IMAP\Client $oClient */
$oClient = \Webklex\IMAP\Facades\Client::account('default');
$oClient->connect();

/** @var \Webklex\IMAP\Folder $folder */
$folder = $oClient->getFolder('INBOX');

/** @var \Webklex\IMAP\Message $message */
$message = $folder->query()->limit(1)->get()->first();

$html = $message->getHTMLBody(function($body, $oAttachment){
    /** @var \Webklex\IMAP\Attachment $oAttachment */
    if ($oAttachment->id && $oAttachment->getImgSrc() != null) {
        $oMessage = $oAttachment->getMessage();

        $image_url = route('my.custom.imap.attachment.route', [
            'muid' => urlencode($oMessage->uid),
            'mid' => urlencode($oMessage->message_id),
            'mti' => urlencode($oMessage->date->timestamp),
            'aid' => urlencode($oAttachment->id)
        ]);

        $body = str_replace('cid:'.$oAttachment->id, $image_url, $body);
    }

    return $body;
});

//Alternative way:
$message_mask = $message->mask();
$html = $message_mask->getHTMLBodyWithEmbeddedUrlImages('my.custom.imap.attachment.route');