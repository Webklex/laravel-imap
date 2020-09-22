<?php
/*
* File: custom_message_mask.php
* Category: Example
* Author: M.Goldenbaum
* Created: 14.03.19 18:47
* Updated: -
*
* Description:
*  -
*/

class CustomAttachmentMask extends \Webklex\PHPIMAP\Support\Masks\AttachmentMask {

    /**
     * New custom method which can be called through a mask
     * @return string
     */
    public function token(){
        return implode('-', [$this->id, $this->getMessage()->getUid(), $this->name]);
    }

    /**
     * Custom attachment saving method
     * @return bool
     */
    public function custom_save() {
        $path = storage_path('foo');
        $filename = $this->token();

        $path = substr($path, -1) == DIRECTORY_SEPARATOR ? $path : $path.DIRECTORY_SEPARATOR;

        return \Illuminate\Support\Facades\File::put($path.$filename, $this->getContent()) !== false;
    }

}

/** @var \Webklex\PHPIMAP\Client $oClient */
$oClient = \Webklex\IMAP\Facades\Client::account('default');
$oClient->connect();
$oClient->setDefaultAttachmentMask(CustomAttachmentMask::class);

/** @var \Webklex\PHPIMAP\Folder $folder */
$folder = $oClient->getFolder('INBOX');

/** @var \Webklex\PHPIMAP\Message $message */
$message = $folder->query()->limit(1)->get()->first();

/** @var \Webklex\PHPIMAP\Attachment $attachment */
$attachment = $message->getAttachments()->first();

/** @var CustomAttachmentMask $masked_attachment */
$masked_attachment = $attachment->mask();

echo 'Token for uid ['.$masked_attachment->getMessage()->getUid().']: '.$masked_attachment->token();

$masked_attachment->custom_save();