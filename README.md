# IMAP Library for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

## Description

Laravel IMAP is an easy way to integrate the native php imap library into your **Laravel** app.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Documentation](#documentation)
  - [Client::class](#clientclass)
  - [Message::class](#messageclass)
  - [Folder::class](#folderclass)
  - [Attachment::class](#attachmentclass) 
  - [MessageCollection::class](#messagecollectionclass) 
  - [AttachmentCollection::class](#attachmentcollectionclass) 
  - [FolderCollection::class](#foldercollectionclass) 
- [Known issues](#known-issues)
- [Milestones & upcoming features](#milestones--upcoming-features)
- [Security](#security)
- [Credits](#credits)
- [Supporters](#supporters)
- [License](#license)

## Installation

1) Install the php-imap library if it isn't already installed:

``` shell
sudo apt-get install php*-imap && sudo apache2ctl graceful
```

You might also want to check `phpinfo()` if the extension is enabled.

2) Now install the Laravel IMAP package by running the following command:

``` shell
composer require webklex/laravel-imap
```

3) Open your `config/app.php` file and add the following to the `providers` array:

``` php
Webklex\IMAP\Providers\LaravelServiceProvider::class,
```

4) In the same `config/app.php` file add the following to the `aliases ` array: 

``` php
'Client' => Webklex\IMAP\Facades\Client::class,
```

5) Run the command below to publish the package config file [config/imap.php](src/config/imap.php):

``` shell
php artisan vendor:publish --provider="Webklex\IMAP\Providers\LaravelServiceProvider"
```

## Configuration

If you are planning to use a single account, you might want to add the following to
your `.env` file.

```
IMAP_HOST=somehost.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true
IMAP_USERNAME=root@example.com
IMAP_PASSWORD=secret
IMAP_DEFAULT_ACCOUNT=default
```

The following encryption methods are supported:
- `false` &mdash; Disable encryption 
- `ssl` &mdash; Use SSL
- `tls` &mdash; Use TLS

Detailed [config/imap.php](src/config/imap.php) configuration:
 - `default` &mdash; used default account
 - `accounts` &mdash; all available accounts
   - `default` &mdash; default account identifier
     - `host` &mdash; imap host
     - `port` &mdash; imap port
     - `encryption` &mdash; desired encryption method
     - `validate_cert` &mdash; decide weather you want to verify the certificate or not
     - `username` &mdash; imap account username
     - `password` &mdash; imap account password
 - `options` &mdash; additional fetch options
   - `delimiter` &mdash; you can use any supported char such as ".", "/", etc
   - `fetch` &mdash; `FT_UID` (message marked as read by fetching the message) or `FT_PEEK` (fetch the message without setting the "read" flag)
   - `fetch_body` &mdash; If set to `false` all messages will be fetched without the body and any potential attachments
   - `fetch_attachment` &mdash;  If set to `false` all messages will be fetched without any attachments
   - `open` &mdash; special configuration for imap_open()
     - `DISABLE_AUTHENTICATOR` &mdash; Disable authentication properties.

## Usage

This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not ben
tested in real live but it gives an impression on how things work.

``` php
use Webklex\IMAP\Client;

$oClient = new Client([
    'host'          => 'somehost.com',
    'port'          => 993,
    'encryption'    => 'ssl',
    'validate_cert' => true,
    'username'      => 'username',
    'password'      => 'password',
]);

//Connect to the IMAP Server
$oClient->connect();

//Get all Mailboxes
/** @var \Webklex\IMAP\Support\FolderCollection $aFolder */
$aFolder = $oClient->getFolders();

//Loop through every Mailbox
/** @var \Webklex\IMAP\Folder $oFolder */
foreach($aFolder as $oFolder){

    //Get all Messages of the current Mailbox $oFolder
    /** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
    $aMessage = $oFolder->getMessages();
    
    /** @var \Webklex\IMAP\Message $oMessage */
    foreach($aMessage as $oMessage){
        echo $oMessage->subject.'<br />';
        echo 'Attachments: '.$oMessage->getAttachments()->count().'<br />';
        echo $oMessage->getHTMLBody(true);
        
        //Move the current Message to 'INBOX.read'
        if($oMessage->moveToFolder('INBOX.read') == true){
            echo 'Message has ben moved';
        }else{
            echo 'Message could not be moved';
        }
    }
}
```

If you use the Facade [\Webklex\IMAP\Facades\Client::class](src/IMAP/Facades/Client.php) please select an account first:

``` php
use Webklex\IMAP\Facades\Client;

$oClient = Client::account('default');
$oClient->connect();
```

There is an experimental function available to get a Folder instance by name. 
For an easier access please take a look at the new config option `imap.options.delimiter` however the `getFolder` 
method takes three options: the required (string) $folder_name and two optional variables. An integer $attributes which 
seems to be sometimes 32 or 64 (I honestly have no clue what this number does, so feel free to enlighten me and anyone 
else) and a delimiter which if it isn't set will use the default option configured inside the [config/imap.php](src/config/imap.php) file.
``` php
/** @var \Webklex\IMAP\Client $oClient */

/** @var \Webklex\IMAP\Folder $oFolder */
$oFolder = $oClient->getFolder('INBOX.name');
```

Search for specific emails:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

//Get all messages since march 15 2018
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['SINCE', Carbon::parse('15.03.2018')->format('d M y')]]);

//Get all messages containing "hello world"
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['TEXT', 'hello world']]);

//Get all unseen messages containing "hello world"
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['UNSEEN'], ['TEXT', 'hello world']]);
```

Available search criteria:
- `ALL` &mdash; return all messages matching the rest of the criteria
- `ANSWERED` &mdash; match messages with the \\ANSWERED flag set
- `BCC` "string" &mdash; match messages with "string" in the Bcc: field
- `BEFORE` "date" &mdash; match messages with Date: before "date"
- `BODY` "string" &mdash; match messages with "string" in the body of the message
- `CC` "string" &mdash; match messages with "string" in the Cc: field
- `DELETED` &mdash; match deleted messages
- `FLAGGED` &mdash; match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
- `FROM` "string" &mdash; match messages with "string" in the From: field
- `KEYWORD` "string" &mdash; match messages with "string" as a keyword
- `NEW` &mdash; match new messages
- `OLD` &mdash; match old messages
- `ON` "date" &mdash; match messages with Date: matching "date"
- `RECENT` &mdash; match messages with the \\RECENT flag set
- `SEEN` &mdash; match messages that have been read (the \\SEEN flag is set)
- `SINCE` "date" &mdash; match messages with Date: after "date"
- `SUBJECT` "string" &mdash; match messages with "string" in the Subject:
- `TEXT` "string" &mdash; match messages with text "string"
- `TO` "string" &mdash; match messages with "string" in the To:
- `UNANSWERED` &mdash; match messages that have not been answered
- `UNDELETED` &mdash; match messages that are not deleted
- `UNFLAGGED` &mdash; match messages that are not flagged
- `UNKEYWORD` "string" &mdash; match messages that do not have the keyword "string"
- `UNSEEN` &mdash; match messages which have not been read yet

Further information:
- http://php.net/manual/en/function.imap-search.php
- https://tools.ietf.org/html/rfc1176
- https://tools.ietf.org/html/rfc1064
- https://tools.ietf.org/html/rfc822
     
Paginate a message collection:
``` php
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $aMessage->paginate();
```

Get a specific message by uid (Please note that the uid is not unique and can change):
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Webklex\IMAP\Message $oMessage */
$oMessage = $oFolder->getMessage($uid = 1);
```

Flag or "unflag" a message:
``` php
/** @var \Webklex\IMAP\Message $oMessage */
$oMessage->setFlag(['Seen', 'Spam']);
$oMessage->unsetFlag('Spam');
```

Save message attachments:
``` php
/** @var \Webklex\IMAP\Message $oMessage */

/** @var \Webklex\IMAP\Support\AttachmentCollection $aAttachment */
$aAttachment = $oMessage->getAttachments();

$aAttachment->each(function ($oAttachment) {
    /** @var \Webklex\IMAP\Attachment $oAttachment */
    $oAttachment->save();
});
```

Fetch messages without body fetching (decrease load):
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['TEXT', 'Hello world']], null, false);

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->getMessages('ALL', null, false);
```

Fetch messages without body and attachment fetching (decrease load):
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['TEXT', 'Hello world']], null, false, 'UTF-8', false);

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->getMessages('ALL', null, false, false);
```

## Documentation
### [Client::class](src/IMAP/Client.php)
| Method              | Arguments                                                                       | Return            | Description                                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------- | :---------------: | ----------------------------------------------------------------------------------------------------------------------------  |
| setConfig           | array $config                                                                   | self              | Set the Client configuration. Take a look at `config/imap.php` for more inspiration.                                          |
| getConnection       | resource $connection                                                            | resource          | Get the current imap resource                                                                                                 |
| setReadOnly         | bool $readOnly                                                                  | self              | Set read only property and reconnect if it's necessary.                                                                       |
| setFetchOption      | integer $option                                                                 | self              | Fail proof setter for $fetch_option                                                                                           |
| isReadOnly          |                                                                                 | bool              | Determine if connection is in read only mode.                                                                                 |
| isConnected         |                                                                                 | bool              | Determine if connection was established.                                                                                      |
| checkConnection     |                                                                                 |                   | Determine if connection was established and connect if not.                                                                   |
| connect             | int $attempts                                                                   |                   | Connect to server.                                                                                                            |
| disconnect          |                                                                                 |                   | Disconnect from server.                                                                                                       |
| getFolder           | string $folder_name, int $attributes = 32, int or null $delimiter               | Folder            | Get a Folder instance by name                                                                                                 |
| getFolders          | bool $hierarchical, string or null $parent_folder                               | FolderCollection  | Get folders list. If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.  |
| openFolder          | Folder $folder, $attempts                                                       |                   | Open a given folder.                                                                                                          |
| createFolder        | string $name                                                                    |                   | Create a new folder.                                                                                                          |
| getMessages         | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment                              | MessageCollection | Get messages from folder.                                                                                                     |
| getUnseenMessages   | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment                              | MessageCollection | Get Unseen messages from folder.                                                                                              |
| searchMessages      | array $where, Folder $folder, $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment | MessageCollection | Get specific messages from a given folder.                                                                                    |
| getQuota            |                                                                                 | array             | Retrieve the quota level settings, and usage statics per mailbox                                                              |
| getQuotaRoot        | string $quota_root                                                              | array             | Retrieve the quota settings per user                                                                                          |
| countMessages       |                                                                                 | int               | Gets the number of messages in the current mailbox                                                                            |
| countRecentMessages |                                                                                 | int               | Gets the number of recent messages in current mailbox                                                                         |
| getAlerts           |                                                                                 | array             | Returns all IMAP alert messages that have occurred                                                                            |
| getErrors           |                                                                                 | array             | Returns all of the IMAP errors that have occurred                                                                             |
| getLastError        |                                                                                 | string            | Gets the last IMAP error that occurred during this page request                                                               |
| expunge             |                                                                                 | bool              | Delete all messages marked for deletion                                                                                       |
| checkCurrentMailbox |                                                                                 | object            | Check current mailbox                                                                                                         |

### [Message::class](src/IMAP/Message.php)
| Method          | Arguments                     | Return               | Description                            |
| --------------- | ----------------------------- | :------------------: | -------------------------------------- |
| parseBody       |                               | Message              | Parse the Message body                 |
| delete          |                               |                      | Delete the current Message             |
| restore         |                               |                      | Restore a deleted Message              |
| copy            | string $mailbox, int $options |                      | Copy the current Messages to a mailbox |
| move            | string $mailbox, int $options |                      | Move the current Messages to a mailbox |
| moveToFolder    | string $mailbox               |                      | Move the Message into an other Folder  |
| setFlag         | string or array $flag         | boolean              | Set one or many flags                  |
| unsetFlag       | string or array $flag         | boolean              | Unset one or many flags                |
| hasTextBody     |                               |                      | Check if the Message has a text body   |
| hasHTMLBody     |                               |                      | Check if the Message has a html body   |
| getTextBody     |                               | string               | Get the Message text body              |
| getHTMLBody     |                               | string               | Get the Message html body              |
| getAttachments  |                               | AttachmentCollection | Get all message attachments            |
| hasAttachments  |                               | boolean              | Checks if there are any attachments present            |
| getClient       |                               | Client               | Get the current Client instance        |
| getUid          |                               | string               | Get the current UID                    |
| getFetchOptions |                               | string               | Get the current fetch option           |
| getMsglist      |                               | integer              | Get the current message list           |
| getHeaderInfo   |                               | object               | Get the current header_info object     |
| getHeader       |                               | string               | Get the current raw header             |
| getMessageId    |                               | integer              | Get the current message ID             |
| getMessageNo    |                               | integer              | Get the current message number         |
| getSubject      |                               | string               | Get the current subject                |
| getDate         |                               | Carbon               | Get the current date object            |
| getFrom         |                               | array                | Get the current from information       |
| getTo           |                               | array                | Get the current to information         |
| getCc           |                               | array                | Get the current cc information         |
| getBcc          |                               | array                | Get the current bcc information        |
| getReplyTo      |                               | array                | Get the current reply to information   |
| getInReplyTo    |                               | string               | Get the current In-Reply-To            |
| getSender       |                               | array                | Get the current sender information     |
| getBodies       |                               | mixed                | Get the current bodies                 |
| getRawBody      |                               | mixed                | Get the current raw message body       |

### [Folder::class](src/IMAP/Folder.php)
| Method            | Arguments                                                                           | Return            | Description                                    |
| ----------------- | ----------------------------------------------------------------------------------- | :---------------: | ---------------------------------------------- |
| hasChildren       |                                                                                     | bool              | Determine if folder has children.              |
| setChildren       | array $children                                                                     | self              | Set children.                                  |
| getMessage        | integer $uid, integer or null $msglist, int or null fetch_options, bool $fetch_body, bool $fetch_attachment | Message           | Get a specific message from folder.            |
| getMessages       | string $criteria, bool $fetch_body, bool $fetch_attachment                                                  | MessageCollection | Get messages from folder.                      |
| getUnseenMessages | string $criteria, bool $fetch_body, bool $fetch_attachment                                                  | MessageCollection | Get Unseen messages from folder.               |
| searchMessages    | array $where, $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment                     | MessageCollection | Get specific messages from a given folder.     |
| delete            |                                                                                     |                   | Delete the current Mailbox                     |
| move              | string $mailbox                                                                     |                   | Move or Rename the current Mailbox             |
| getStatus         | integer $options                                                                    | object            | Returns status information on a mailbox        |
| appendMessage     | string $message, string $options, string $internal_date                             | bool              | Append a string message to the current mailbox |
| getClient         |                                                                                     | Client            | Get the current Client instance                |
                    
### [Attachment::class](src/IMAP/Attachment.php)
| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getContent     |                                | string or null | Get attachment content                                 |     
| getName        |                                | string or null | Get attachment name                                    |        
| getType        |                                | string or null | Get attachment type                                    |        
| getDisposition |                                | string or null | Get attachment disposition                             | 
| getContentType |                                | string or null | Get attachment content type                            | 
| getImgSrc      |                                | string or null | Get attachment image source as base64 encoded data url |      
| save           | string $path, string $filename | boolean        | Save the attachment content to your filesystem         |      

### [MessageCollection::class](src/IMAP/Support/MessageCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [AttachmentCollection::class](src/IMAP/Support/AttachmentCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [FolderCollection::class](src/IMAP/Support/FolderCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### Known issues
| Error                                                                     | Solution                                                   |
| ------------------------------------------------------------------------- | ---------------------------------------------------------- |
| Kerberos error: No credentials cache file found (try running kinit) (...) | Uncomment "DISABLE_AUTHENTICATOR" inside `config/imap.php` | 
| imap_fetchbody() expects parameter 4 to be long, string given (...)       | Make sure that `imap.options.fetch` is a valid integer     | 
| Use of undefined constant FT_UID - assumed 'FT_UID' (...)                 | Please take a look at [#14](https://github.com/Webklex/laravel-imap/issues/14) [#30](https://github.com/Webklex/laravel-imap/issues/30)     | 
| DateTime::__construct(): Failed to parse time string (...)                | Please report any new invalid timestamps to [#45](https://github.com/Webklex/laravel-imap/issues/45)  | 
| imap_open(): Couldn't open (...) Please log in your web browser: (...)    | In order to use IMAP on some services (such as Gmail) you need to enable it first. [Google help page]( https://support.google.com/mail/answer/7126229?hl=en) |
| imap_headerinfo(): Bad message number                                     | This happens if no Message number is available. Please make sure Message::parseHeader() has run before |

## Milestones & upcoming features
* Wiki!!

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email github@webklex.com instead of using the issue tracker.

## Credits

- [Webklex][link-author]
- [All Contributors][link-contributors]

## Supporters

A special thanks to Jetbrains for supporting this project with their [open source license program](https://www.jetbrains.com/buy/opensource/).

[![Jetbrains][png-jetbrains]][link-jetbrains]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/Webklex/laravel-imap.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Webklex/laravel-imap/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/Webklex/laravel-imap.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Webklex/laravel-imap.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Webklex/laravel-imap.svg?style=flat-square
[ico-gittip]: http://img.shields.io/gittip/webklex.svg
[png-jetbrains]: https://www.webklex.com/jetbrains.png

[link-packagist]: https://packagist.org/packages/Webklex/laravel-imap
[link-travis]: https://travis-ci.org/Webklex/laravel-imap
[link-scrutinizer]: https://scrutinizer-ci.com/g/Webklex/laravel-imap/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Webklex/laravel-imap
[link-downloads]: https://packagist.org/packages/Webklex/laravel-imap
[link-author]: https://github.com/webklex
[link-contributors]: https://github.com/Webklex/laravel-imap/graphs/contributors
[link-jetbrains]: https://www.jetbrains.com
