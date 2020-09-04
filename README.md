# IMAP Library for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

## Description

Laravel IMAP is an easy way to integrate the native php imap library into your **Laravel** app.

> If you want to use this library outside of Laravel, please head over to [webklex/php-imap](https://github.com/Webklex/php-imap)

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Basic usage example](#basic-usage-example)
    - [Facade](#facade)
    - [Folder / Mailbox](#folder--mailbox)
    - [Search](#search-for-messages)
    - [Counting messages](#counting-messages)
    - [Result limiting](#result-limiting)
    - [Pagination](#pagination)
    - [View examples](#view-examples)
    - [Fetch a specific message](#fetch-a-specific-message)
    - [Message flags](#message-flags)
    - [Attachments](#attachments)
    - [Advanced fetching](#advanced-fetching)
    - [Masking](#masking)
    - [Idle](#idle)
    - [Events](#events)
    - [Specials](#specials)
- [Support](#support)
- [Documentation](#documentation)
  - [Client::class](#clientclass)
  - [Message::class](#messageclass)
  - [Folder::class](#folderclass)
  - [Query::class](#queryclass)
  - [Attachment::class](#attachmentclass) 
  - [Mask::class](#maskclass) 
    - [MessageMask::class](#messagemaskclass) 
    - [AttachmentMask::class](#attachmentmaskclass) 
  - [MessageCollection::class](#messagecollectionclass) 
  - [AttachmentCollection::class](#attachmentcollectionclass) 
  - [FolderCollection::class](#foldercollectionclass) 
  - [FlagCollection::class](#flagcollectionclass) 
- [Known issues](#known-issues)
- [Milestones & upcoming features](#milestones--upcoming-features)
- [Security](#security)
- [Credits](#credits)
- [Supporters](#supporters)
- [License](#license)

## Installation

1) Install the php-imap library if it isn't already installed:

``` shell
sudo apt-get install php*-imap php*-mbstring php*-mcrypt && sudo apache2ctl graceful
```

You might also want to check `phpinfo()` if the extension is enabled.

2) Now install the Laravel IMAP package by running the following command:

``` shell
composer require webklex/laravel-imap
```

3) If you're running Laravel >= 5.5, package discovery will configure the service provider and `Client` alias out of the box.

    Otherwise, for Laravel <= 5.4, edit your `config/app.php` file and:

    - add the following to the `providers` array:
        ``` php
        Webklex\IMAP\Providers\LaravelServiceProvider::class,
        ```
    - add the following to the `aliases` array: 
        ``` php
        'Client' => Webklex\IMAP\Facades\Client::class,
        ```

4) Run the command below to publish the package config file [config/imap.php](src/config/imap.php):

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
IMAP_PROTOCOL=imap
```

Supported protocols:
- `imap` &mdash; Use IMAP [default]
- `pop3` &mdash; Use POP3
- `nntp` &mdash; Use NNTP

The following encryption methods are supported:
- `false` &mdash; Disable encryption 
- `ssl` &mdash; Use SSL
- `tls` &mdash; Use TLS
- `starttls` &mdash; Use STARTTLS (alias TLS)
- `notls` &mdash; Use NoTLS

Detailed [config/imap.php](src/config/imap.php) configuration:
 - `default` &mdash; used default account. It will be used as default for any missing account parameters. If however the default account is missing a parameter the package default will be used. Set to `false` to disable this functionality.
 - `accounts` &mdash; all available accounts
   - `default` &mdash; The account identifier (in this case `default` but could also be `fooBar` etc).
     - `host` &mdash; imap host
     - `port` &mdash; imap port
     - `encryption` &mdash; desired encryption method
     - `validate_cert` &mdash; decide weather you want to verify the certificate or not
     - `username` &mdash; imap account username
     - `password` &mdash; imap account password
 - `date_format` &mdash; The default date format is used to convert any given Carbon::class object into a valid date string. (`d-M-Y`, `d-M-y`, `d M y`)
 - `options` &mdash; additional fetch options
   - `delimiter` &mdash; you can use any supported char such as ".", "/", etc
   - `fetch` &mdash; `IMAP::FT_UID` (message marked as read by fetching the message) or `IMAP::FT_PEEK` (fetch the message without setting the "read" flag)
   - `fetch_body` &mdash; If set to `false` all messages will be fetched without the body and any potential attachments
   - `fetch_attachment` &mdash;  If set to `false` all messages will be fetched without any attachments
   - `fetch_flags` &mdash;  If set to `false` all messages will be fetched without any flags
   - `message_key` &mdash; Message key identifier option
   - `fetch_order` &mdash; Message fetch order
   - `open` &mdash; special configuration for imap_open()
     - `DISABLE_AUTHENTICATOR` &mdash; Disable authentication properties.
   - `decoder` &mdash; Currently only the message subject and attachment name decoder can be set
   - `masks` &mdash; Default [masking](#masking) config
     - `message` &mdash; Default message mask
     - `attachment` &mdash; Default attachment mask

## Usage
#### Basic usage example
This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not be
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
    'protocol'      => 'imap'
]);
/* Alternative by using the Facade
$oClient = Webklex\IMAP\Facades\Client::account('default');
*/

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
    $aMessage = $oFolder->messages()->all()->get();
    
    /** @var \Webklex\IMAP\Message $oMessage */
    foreach($aMessage as $oMessage){
        echo $oMessage->getSubject().'<br />';
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

#### Facade
If you use the Facade [\Webklex\IMAP\Facades\Client::class](src/IMAP/Facades/Client.php) please select an account first:

``` php
use Webklex\IMAP\Facades\Client;

$oClient = Client::account('default');
$oClient->connect();
```

#### Folder / Mailbox
There is an experimental function available to get a Folder instance by name. 
For an easier access please take a look at the new config option `imap.options.delimiter` however the `getFolder` 
method takes three options: the required (string) $folder_name and two optional variables. An integer $attributes which 
seems to be sometimes 32 or 64 (I honestly have no clue what this number does, so feel free to enlighten me and anyone 
else) and a delimiter which if it isn't set will use the default option configured inside the [config/imap.php](src/config/imap.php) file.
> If you are using Exchange you might want to set all parameter and the last `$prefix_address` to `false` e.g. `$oClient->getFolder('name', 32, null, false)` #234
``` php
/** @var \Webklex\IMAP\Client $oClient */

/** @var \Webklex\IMAP\Folder $oFolder */
$oFolder = $oClient->getFolder('INBOX.name');
```

List all available folders:
``` php
/** @var \Webklex\IMAP\Client $oClient */

/** @var \Webklex\IMAP\Support\FolderCollection $aFolder */
$aFolder = $oClient->getFolders();
```


#### Search for messages
Search for specific emails:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

//Get all messages
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->all()->get();

//Get all messages from example@domain.com
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->from('example@domain.com')->get();

//Get all messages since march 15 2018
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->since('15.03.2018')->get();

//Get all messages within the last 5 days
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->since(now()->subDays(5))->get();
//Or for older laravel versions..
$aMessage = $oFolder->query()->since(\Carbon::now()->subDays(5))->get();

//Get all messages containing "hello world"
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('hello world')->get();

//Get all unseen messages containing "hello world"
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->unseen()->text('hello world')->get();

//Extended custom search query for all messages containing "hello world" 
//and have been received since march 15 2018
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('hello world')->since('15.03.2018')->get();
$aMessage = $oFolder->query()->Text('hello world')->Since('15.03.2018')->get();
$aMessage = $oFolder->query()->whereText('hello world')->whereSince('15.03.2018')->get();

// Build a custom search query
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()
->where([['TEXT', 'Hello world'], ['SINCE', \Carbon::parse('15.03.2018')]])
->get();

//!EXPERIMENTAL!
//Get all messages NOT containing "hello world"
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->notText('hello world')->get();
$aMessage = $oFolder->query()->not_text('hello world')->get();
$aMessage = $oFolder->query()->not()->text('hello world')->get();

//Get all messages by custom search criteria
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->where(["CUSTOM_FOOBAR" => "fooBar"])->get();

```

Available search aliases for a better code reading:
``` php
// Folder::search() is just an alias for Folder::query()
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->search()->text('hello world')->since('15.03.2018')->get();

// Folder::messages() is just an alias for Folder::query()
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->messages()->text('hello world')->since('15.03.2018')->get();

```
All available query / search methods can be found here: [Query::class](src/IMAP/Query/WhereQuery.php)

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
- `NOT` &mdash; not matching
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
- https://gist.github.com/martinrusev/6121028

#### Result limiting
Limiting the request emails:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

//Get all messages for page 2 since march 15 2018 where each page contains 10 messages
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->since('15.03.2018')->limit(10, 2)->get();
```

#### Counting messages
Count all available messages matching the current search criteria:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

//Count all messages
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$count = $oFolder->query()->all()->count();

//Count all messages since march 15 2018
$count = $oFolder->query()->since('15.03.2018')->count();
```

#### Pagination
Paginate a query:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $oFolder->query()->since('15.03.2018')->paginate();
```
Paginate a message collection:
``` php
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $aMessage->paginate();
```
Blade example for a paginated list:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
$paginator = $oFolder->search()
->since(\Carbon::now()->subDays(14))->get()
->paginate($perPage = 5, $page = null, $pageName = 'imap_blade_example');
```
``` html
<table>
    <thead>
        <tr>
            <th>UID</th>
            <th>Subject</th>
            <th>From</th>
            <th>Attachments</th>
        </tr>
    </thead>
    <tbody>
        @if($paginator->count() > 0)
            @foreach($paginator as $oMessage)
                <tr>
                    <td>{{$oMessage->getUid()}}</td>
                    <td>{{$oMessage->getSubject()}}</td>
                    <td>{{$oMessage->getFrom()[0]->mail}}</td>
                    <td>{{$oMessage->getAttachments()->count() > 0 ? 'yes' : 'no'}}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="4">No messages found</td>
            </tr>
        @endif
    </tbody>
</table>

{{$paginator->links()}}
```
> You can also paginate a Folder-, Attachment- or FlagCollection instance.

#### View examples
You can find a few blade examples under [/examples](https://github.com/Webklex/laravel-imap/tree/master/examples).

#### Fetch a specific message
Get a specific message by uid (Please note that the uid is not unique and can change):
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Webklex\IMAP\Message $oMessage */
$oMessage = $oFolder->getMessage($uid = 1);
```

#### Message flags
Flag or "unflag" a message:
``` php
/** @var \Webklex\IMAP\Message $oMessage */
$oMessage->setFlag(['Seen', 'Spam']);
$oMessage->unsetFlag('Spam');
```

Mark all messages as "read" while fetching:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('Hello world')->markAsRead()->get();
```

Don't mark all messages as "read" while fetching:
``` php
/** @var \Webklex\IMAP\Folder $oFolder */
/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->text('Hello world')->leaveUnread()->get();
```

#### Attachments
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

#### Advanced fetching
Fetch messages without body fetching (decrease load):
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereText('Hello world')->setFetchBody(false)->get();

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereAll()->setFetchBody(false)->setFetchAttachment();
```

Fetch messages without body, flag and attachment fetching (decrease load):
``` php
/** @var \Webklex\IMAP\Folder $oFolder */

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereText('Hello world')
->setFetchFlags(false)
->setFetchBody(false)
->setFetchAttachment(false)
->get();

/** @var \Webklex\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->query()->whereAll()
->setFetchFlags(false)
->setFetchBody(false)
->setFetchAttachment(false)
->get();
```

#### Masking
Laravel-IMAP already comes with two default masks [MessageMask::class](#messagemaskclass) and [AttachmentMask::class](#attachmentmaskclass).

The masked instance has to be called manually and is designed to add custom functionality.

You can call the default mask by calling the mask method without any arguments.
``` php
/** @var \Webklex\IMAP\Message $oMessage */
$mask = $oMessage->mask();
```

There are several methods available to set the default mask:
``` php
/** @var \Webklex\IMAP\Client $oClient */
/** @var \Webklex\IMAP\Message $oMessage */

$message_mask = \Webklex\IMAP\Support\Masks\MessageMask::class;

$oClient->setDefaultMessageMask($message_mask);
$oMessage->setMask($message_mask);
$mask = $oMessage->mask($message_mask);
```
The last one wont set the mask but generate a masked instance using the provided mask.

You could also set the default masks inside your `config/imap.php` file under `masks`.

You can also apply a mask on [attachments](#attachmentclass):
``` php
/** @var \Webklex\IMAP\Client $oClient */
/** @var \Webklex\IMAP\Attachment $oAttachment */
$attachment_mask = \Webklex\IMAP\Support\Masks\AttachmentMask::class;

$oClient->setDefaultAttachmentMask($attachment_mask);
$oAttachment->setMask($attachment_mask);
$mask = $oAttachment->mask($attachment_mask);
```

If you want to implement your own mask just extend [MessageMask::class](#messagemaskclass), [AttachmentMask::class](#attachmentmaskclass)
or [Mask::class](#maskclass) and implement your desired logic:

``` php
/** @var \Webklex\IMAP\Message $oMessage */
class CustomMessageMask extends \Webklex\IMAP\Support\Masks\MessageMask {

    /**
     * New custom method which can be called through a mask
     * @return string
     */
    public function token(){
        return implode('-', [$this->message_id, $this->uid, $this->message_no]);
    }
}

$mask = $oMessage->mask(CustomMessageMask::class);

echo $mask->token().'@'.$mask->uid;
```

Additional examples can be found here:
- [Custom message mask](https://github.com/Webklex/laravel-imap/blob/master/examples/custom_message_mask.php)
- [Custom attachment mask](https://github.com/Webklex/laravel-imap/blob/master/examples/custom_attachment_mask.php)


#### Idle
Use the `Query::idle()` function to have an instance running which behaves like an idle imap connection.
The callback and `Webklex\IMAP\Events\MessageNewEvent($message)` event get fired by every new incoming email.
``` php
$timeout = 10;
/** @var \Webklex\IMAP\Folder $folder */
$folder->query()->subject('test')->idle(function($message){
    /** @var \Webklex\IMAP\Message $message */
    dump("new message", $message->subject);
}, $timeout);
```

#### Events
The following events are available:
- `Webklex\IMAP\Events\MessageDeletedEvent($message)` &mdash; triggered by `Message::delete`
- `Webklex\IMAP\Events\MessageRestoredEvent($message)` &mdash; triggered by `Message::restore`
- `Webklex\IMAP\Events\MessageMovedEvent($old_message, $new_message)` &mdash; triggered by `Message::move`
- `Webklex\IMAP\Events\MessageNewEvent($message)` &mdash; can get triggered by `Query::idle()`

Additional integration information:
- https://laravel.com/docs/7.x/events#event-subscribers
- https://laravel.com/docs/5.2/events#event-subscribers

#### Specials
Find the folder containing a message:
``` php
$oFolder = $aMessage->getContainingFolder();
```

## Support
If you encounter any problems or if you find a bug, please don't hesitate to create a new [issue](https://github.com/Webklex/laravel-imap/issues).
However please be aware that it might take some time to get an answer.

If you need **immediate** or **commercial** support, feel free to send me a mail at github@webklex.com. 

##### A little notice
If you write source code in your issue, please consider to format it correctly. This makes it so much nicer to read 
and people are more likely to comment and help :)

&#96;&#96;&#96; php

echo 'your php code...';

&#96;&#96;&#96;

will turn into:
``` php
echo 'your php code...';
```

### Features & pull requests
Everyone can contribute to this project. Every pull request will be considered but it can also happen to be declined. 
To prevent unnecessary work, please consider to create a [feature issue](https://github.com/Webklex/laravel-imap/issues/new?template=feature_request.md) 
first, if you're planning to do bigger changes. Of course you can also create a new [feature issue](https://github.com/Webklex/laravel-imap/issues/new?template=feature_request.md)
if you're just wishing a feature ;)

>Off topic, rude or abusive issues will be deleted without any notice.

## Documentation
### [Client::class](src/IMAP/Client.php)

| Method                    | Arguments                                                                       | Return            | Description                                                                                                                   |
| ------------------------- | ------------------------------------------------------------------------------- | :---------------: | ----------------------------------------------------------------------------------------------------------------------------  |
| setConfig                 | array $config                                                                   | self              | Set the Client configuration. Take a look at `config/imap.php` for more inspiration.                                          |
| getConnection             | resource $connection                                                            | resource          | Get the current imap resource                                                                                                 |
| setReadOnly               | bool $readOnly                                                                  | self              | Set read only property and reconnect if it's necessary.                                                                       |
| isReadOnly                |                                                                                 | bool              | Determine if connection is in read only mode.                                                                                 |
| isConnected               |                                                                                 | bool              | Determine if connection was established.                                                                                      |
| checkConnection           |                                                                                 |                   | Determine if connection was established and connect if not.                                                                   |
| connect                   | int $attempts                                                                   |                   | Connect to server.                                                                                                            |
| disconnect                |                                                                                 |                   | Disconnect from server.                                                                                                       |
| getFolder                 | string $folder_name, int $attributes = 32, int or null $delimiter, bool $prefix_address | Folder            | Get a Folder instance by name                                                                                                 |
| getFolders                | bool $hierarchical, string or null $parent_folder                               | FolderCollection  | Get folders list. If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.  |
| openFolder                | string or Folder $folder, integer $attempts                                     |                   | Open a given folder.                                                                                                          |
| createFolder              | string $name                                                                    | boolean           | Create a new folder.                                                                                                          |
| renameFolder              | string $old_name, string $new_name                                              | boolean           | Rename a folder. |
| deleteFolder              | string $name                                                                    | boolean           | Delete a folder. |
| getMessages               | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags       | MessageCollection | Get messages from folder.                                                                                 |
| getUnseenMessages         | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags       | MessageCollection | Get Unseen messages from folder.                                                                          |
| searchMessages            | array $where, Folder $folder, $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment, bool $fetch_flags | MessageCollection | Get specific messages from a given folder.                                         |
| overview                  | string $sequence = "1:*", int $option = IMAP::NIL                               | Collection        | Read an overview of the information in the headers of a given message or sequence.                                         |
| getQuota                  |                                                                                 | array             | Retrieve the quota level settings, and usage statics per mailbox                                                              |
| getQuotaRoot              | string $quota_root                                                              | array             | Retrieve the quota settings per user                                                                                          |
| countMessages             |                                                                                 | int               | Gets the number of messages in the current mailbox                                                                            |
| countRecentMessages       |                                                                                 | int               | Gets the number of recent messages in current mailbox                                                                         |
| getAlerts                 |                                                                                 | array             | Returns all IMAP alert messages that have occurred                                                                            |
| getErrors                 |                                                                                 | array             | Returns all of the IMAP errors that have occurred                                                                             |
| getLastError              |                                                                                 | string            | Gets the last IMAP error that occurred during this page request                                                               |
| expunge                   |                                                                                 | bool              | Delete all messages marked for deletion                                                                                       |
| checkCurrentMailbox       |                                                                                 | object            | Check current mailbox                                                                                                         |
| setTimeout                | string or int $type, int $timeout                                               | boolean           | Set the timeout for certain imap operations: 1: Open, 2: Read, 3: Write, 4: Close                                             |
| getTimeout                | string or int $type                                                             | int               | Check current mailbox                                                                                                         |
| setDefaultMessageMask     | string $mask                                                                    | self              | Set the default message mask class                                                                                            |
| getDefaultMessageMask     |                                                                                 | string            | Get the current default message mask class name                                                                               | 
| setDefaultAttachmentMask  | string $mask                                                                    | self              | Set the default attachment mask class                                                                                         |
| getDefaultAttachmentMask  |                                                                                 | string            | Get the current default attachment mask class name                                                                            | 
| getFolderPath             |                                                                                 | string            | Get the current folder path                                                                                                   | 

### [Message::class](src/IMAP/Message.php)

| Method          | Arguments                     | Return               | Description                            |
| --------------- | ----------------------------- | :------------------: | -------------------------------------- |
| parseBody       |                               | Message              | Parse the Message body                 |
| delete          | boolean $expunge              | boolean              | Delete the current Message             |
| restore         | boolean $expunge              | boolean              | Restore a deleted Message              |
| copy            | string $mailbox, int $options | boolean              | Copy the current Messages to a mailbox |
| move            | string $mailbox, int $options | boolean              | Move the current Messages to a mailbox |
| getContainingFolder | Folder or null $folder    | Folder or null       | Get the folder containing the message  |
| moveToFolder    | string $mailbox, boolean $expunge, boolean $create_folder | Message | Move the Message into an other Folder  |
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
| getMessageNo    |                               | integer              | Get the current message number         |
| getPriority     |                               | integer              | Get the current message priority       |
| getSubject      |                               | string               | Get the current subject                |
| getReferences   |                               | mixed                | Get any potentially present references |
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
| getToken        |                               | string               | Get a base64 encoded message token     |
| getFlags        |                               | FlagCollection       | Get the current message flags          |
| is              |                               | boolean              | Does this message match another one?   |
| getStructure    |                               | object               | The raw message structure              |
| getFolder       |                               | Folder               | The current folder                     |
| mask            | string $mask = null           | Mask                 | Get a masked instance                  |
| setMask         | string $mask                  | Message              | Set the mask class                     |
| getMask         |                               | string               | Get the current mask class name        |

### [Folder::class](src/IMAP/Folder.php)

| Method            | Arguments                                                                           | Return            | Description                                    |
| ----------------- | ----------------------------------------------------------------------------------- | :---------------: | ---------------------------------------------- |
| hasChildren       |                                                                                     | bool              | Determine if folder has children.              |
| setChildren       | array $children                                                                     | self              | Set children.                                  |
| getMessage        | integer $uid, integer or null $msglist, int or null fetch_options, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags | Message           | Get a specific message from folder.            |
| getMessages       | string $criteria, int or null $fetch_options, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags                                                  | MessageCollection | Get messages from folder.                      |
| getUnseenMessages | string $criteria, int or null $fetch_options, bool $fetch_body, bool $fetch_attachment, bool $fetch_flags                                                  | MessageCollection | Get Unseen messages from folder.               |
| searchMessages    | array $where, int or null $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment, bool $fetch_flags                     | MessageCollection | Get specific messages from a given folder.     |
| delete            |                                                                                     |                   | Delete the current Mailbox                     |
| move              | string $mailbox                                                                     |                   | Move or Rename the current Mailbox             |
| getStatus         | integer $options                                                                    | object            | Returns status information on a mailbox        |
| appendMessage     | string $message, string $options, string $internal_date                             | bool              | Append a string message to the current mailbox |
| getClient         |                                                                                     | Client            | Get the current Client instance                |
| query             | string $charset = 'UTF-8'                                                           | WhereQuery        | Get the current Client instance                |
| messages          | string $charset = 'UTF-8'                                                           | WhereQuery        | Alias for Folder::query()                      |
| search            | string $charset = 'UTF-8'                                                           | WhereQuery        | Alias for Folder::query()                      |
| getEncodedName    |                                                                                     | string            | Get the encoded name                           |
      
### [Query::class](src/IMAP/Query/WhereQuery.php)

| Method             | Arguments                         | Return            | Description                                    |
| ------------------ | --------------------------------- | :---------------: | ---------------------------------------------- |
| where              | mixed $criteria, $value = null    | WhereQuery        | Add new criteria to the current query |
| orWhere            | Closure $closure                  | WhereQuery        | If supported you can perform extended search requests |
| andWhere           | Closure $closure                  | WhereQuery        | If supported you can perform extended search requests |
| all                |                                   | WhereQuery        | Select all available messages |
| answered           |                                   | WhereQuery        | Select answered messages |
| deleted            |                                   | WhereQuery        | Select deleted messages |
| new                |                                   | WhereQuery        | Select new messages |
| not                |                                   | WhereQuery        | Not select messages |
| old                |                                   | WhereQuery        | Select old messages |
| recent             |                                   | WhereQuery        | Select recent messages |
| seen               |                                   | WhereQuery        | Select seen messages |
| unanswered         |                                   | WhereQuery        | Select unanswered messages |
| undeleted          |                                   | WhereQuery        | Select undeleted messages |
| unflagged          |                                   | WhereQuery        | Select unflagged messages |
| unseen             |                                   | WhereQuery        | Select unseen messages |
| noXSpam            |                                   | WhereQuery        | Select as no xspam flagged messages |
| isXSpam            |                                   | WhereQuery        | Select as xspam flagged messages |
| language           | string $value                     | WhereQuery        | Select messages matching a given language |
| unkeyword          | string $value                     | WhereQuery        | Select messages matching a given unkeyword |
| messageId          | string $value                     | WhereQuery        | Select messages matching a given message id |
| to                 | string $value                     | WhereQuery        | Select messages matching a given receiver (To:) |
| text               | string $value                     | WhereQuery        | Select messages matching a given text body |
| subject            | string $value                     | WhereQuery        | Select messages matching a given subject |
| since              | string $value                     | WhereQuery        | Select messages since a given date |
| on                 | string $value                     | WhereQuery        | Select messages on a given date |
| keyword            | string $value                     | WhereQuery        | Select messages matching a given keyword |
| from               | string $value                     | WhereQuery        | Select messages matching a given sender (From:) |
| flagged            | string $value                     | WhereQuery        | Select messages matching a given flag |
| cc                 | string $value                     | WhereQuery        | Select messages matching a given receiver (CC:) |
| body               | string $value                     | WhereQuery        | Select messages matching a given HTML body |
| before             | string $value                     | WhereQuery        | Select messages before a given date |
| bcc                | string $value                     | WhereQuery        | Select messages matching a given receiver (BCC:) |
| count              |                                   | integer           | Count all available messages matching the current search criteria |
| get                |                                   | MessageCollection | Fetch messages with the current query |
| idle               | mixed $callback($message), int $timeout = 10 |        | Simulate an idle connection to receive new messages "live" |
| limit              | integer $limit, integer $page = 1 | WhereQuery        | Limit the amount of messages being fetched |
| setFetchOptions    | boolean $fetch_options            | WhereQuery        | Set the fetch options |
| setFetchBody       | boolean $fetch_body               | WhereQuery        | Set the fetch body option |
| getFetchAttachment | boolean $fetch_attachment         | WhereQuery        | Set the fetch attachment option |
| setFetchFlags      | boolean $fetch_flags              | WhereQuery        | Set the fetch flags option |
| leaveUnread        |                                   | WhereQuery        | Don't mark all messages as "read" while fetching:  |
| markAsRead         |                                   | WhereQuery        | Mark all messages as "read" while fetching |  
| paginate           | int $perPage = 5, $page = null, $pageName = 'imap_page' | LengthAwarePaginator | Paginate the current query. |
     
           
### [Attachment::class](src/IMAP/Attachment.php)

| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getContent     |                                | string or null | Get attachment content                                 |     
| getMimeType    |                                | string or null | Get attachment mime type                               |     
| getExtension   |                                | string or null | Get a guessed attachment extension                     |     
| getId          |                                | string or null | Get attachment id                                      |        
| getName        |                                | string or null | Get attachment name                                    |        
| getPartNumber  |                                | int or null    | Get attachment part number                             |        
| getSize        |                                | int or null    | Get attachment size                                    |        
| getType        |                                | string or null | Get attachment type                                    |        
| getDisposition |                                | string or null | Get attachment disposition                             | 
| getContentType |                                | string or null | Get attachment content type                            | 
| getImgSrc      |                                | string or null | Get attachment image source as base64 encoded data url |      
| save           | string $path, string $filename | boolean        | Save the attachment content to your filesystem         |    
| mask           | string $mask = null            | Mask           | Get a masked instance                                  |
| setMask        | string $mask                   | Attachment     | Set the mask class                                     |
| getMask        |                                | string         | Get the current mask class name                        |  

### [Mask::class](src/IMAP/Support/Masks/Mask.php)

| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getParent      |                                | Masked parent  | Get the masked parent object                           |     
| getAttributes  |                                | array          | Get all cloned attributes                              |  
| __get          |                                | mixed          | Access any cloned parent attribute                     |  
| __set          |                                | mixed          | Set any cloned parent attribute                        |  
| __inherit      |                                | mixed          | All public methods of the given parent are callable    |

### [MessageMask::class](src/IMAP/Support/Masks/MessageMask.php)

| Method                              | Arguments                              | Return         | Description                                |
| ----------------------------------- | -------------------------------------- | :------------: | ------------------------------------------ |
| getHtmlBody                         |                                        | string or null | Get HTML body                              |     
| getCustomHTMLBody                   | callable or bool $callback             | string or null | Get a custom HTML body                     |  
| getHTMLBodyWithEmbeddedBase64Images |                                        | string or null | Get HTML body with embedded base64 images  |  
| getHTMLBodyWithEmbeddedUrlImages    | string $route_name, array $params = [] | string or null | Get HTML body with embedded routed images  |  

### [AttachmentMask::class](src/IMAP/Support/Masks/AttachmentMask.php)

| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getContentBase64Encoded     |                   | string or null | Get attachment content                                 |     
| getImageSrc    |                                | string or null | Get attachment mime type                               |    


### [MessageCollection::class](src/IMAP/Support/MessageCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [FlagCollection::class](src/IMAP/Support/FlagCollection.php)
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
| imap_open(): Couldn't open stream {outlook.office365.com:993/imap/s (...) | Can be caused by a lot of things: head over to these issues for more information: [#153](https://github.com/Webklex/laravel-imap/issues/153) [#100](https://github.com/Webklex/laravel-imap/issues/100) [#78](https://github.com/Webklex/laravel-imap/issues/78)  |
| imap_fetchheader(): Bad message number | Try to change the `message_key` [#243](https://github.com/Webklex/laravel-imap/issues/243)

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

A special thanks to Jetbrains for supporting this project through their [open source license program](https://www.jetbrains.com/buy/opensource/).

[![Jetbrains][png-jetbrains]][link-jetbrains]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/webklex/laravel-imap.svg?style=flat-square
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
