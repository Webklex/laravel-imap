# IMAP Library for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

## Install

Via Composer

``` bash
$ composer require webklex/laravel-imap
```

## Setup

Add the service provider to the providers array in `config/app.php`.

``` php
'providers' => [
    Webklex\IMAP\Providers\LaravelServiceProvider::class,
];
```

If you are planning to use a single account, you might want to add the following to
your .env file.

```
IMAP_HOST=somehost.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true
IMAP_USERNAME=root@example.com
IMAP_PASSWORD=secret
```

The following encryption methods are supported:
```
false   - Disable encryption 
ssl     - Use SSL
tls     - Use TLS

```

## Publishing

You can publish everything at once

``` php
php artisan vendor:publish --provider="Webklex\IMAP\Providers\LaravelServiceProvider"
```

Access the IMAP Client by its Facade `\Webklex\IMAP\Facades\Client`. 
Therefor you might want to add an alias to the aliases array within the `config/app.php` file.

``` php
'aliases' => [
    'Client' => Webklex\IMAP\Facades\Client::class
];
```

## Usage

This library is designed to handle the native php imap functions more easily and to be 
able to integrate this package within your current laravel installation.

Here is a basic example, which will echo out all Mails within all imap folders
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
$aMailboxes = $oClient->getFolders();

//Loop through every Mailbox
/** @var \Webklex\IMAP\Folder $oMailbox */
foreach($aMailboxes as $oMailbox){

    //Get all Messages of the current Mailbox
    /** @var \Webklex\IMAP\Message $oMessage */
    foreach($oMailbox->getMessages() as $oMessage){
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

If you use the Facade `\Webklex\IMAP\Facades\Client` please select an account first:

``` php
use Webklex\IMAP\Facades\Client;

$oClient = Webklex\IMAP\Facades\Client::account('default');
$oClient->connect();
```

You can define your accounts inside the `config/imap.php` file:
```
'accounts' => [ 
    'default' => [
        'host'  => env('IMAP_HOST', 'localhost'),
        'port'  => env('IMAP_PORT', 993),
        'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => env('IMAP_VALIDATE_CERT', true),
        'username' => env('IMAP_USERNAME', 'root@example.com'),
        'password' => env('IMAP_PASSWORD', ''),
    ], 
    'gmail' => [.. ]
]
```

## Documentation
### [Client::class](src/IMAP/Client.php)
| Method                | Arguments                                         | Return  | Description                                                                                                                   |
| --------------------- | ------------------------------------------------- | :-----: | ----------------------------------------------------------------------------------------------------------------------------  |
| setConfig             | array $config                                     | self    | Set the Client configuration. Take a look at `config/imap.php` for more inspiration.                                          |
| setReadOnly           | bool $readOnly                                    | self    | Set read only property and reconnect if it's necessary.                                                                       |
| isReadOnly            |                                                   | bool    | Determine if connection is in read only mode.                                                                                 |
| isConnected           |                                                   | bool    | Determine if connection was established.                                                                                      |
| checkConnection       |                                                   |         | Determine if connection was established and connect if not.                                                                   |
| connect               | int $attempts                                     |         | Connect to server.                                                                                                            |
| disconnect            |                                                   |         | Disconnect from server.                                                                                                       |
| getFolders            | bool $hierarchical, string or null $parent_folder | array   | Get folders list. If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.  |
| openFolder            | \Webklex\IMAP\Folder $folder                      |         | Open a given folder.                                                                                                          |
| createFolder          | string $name                                      |         | Create a new folder.                                                                                                          |
| getMessages           | \Webklex\IMAP\Folder $folder, string $criteria    | array   | Get messages from folder.                                                                                                     |
| getQuota              |                                                   | array   | Retrieve the quota level settings, and usage statics per mailbox                                                              |
| getQuotaRoot          | string $quota_root                                | array   | Retrieve the quota settings per user                                                                                          |
| countMessages         |                                                   | int     | Gets the number of messages in the current mailbox                                                                            |
| countRecentMessages   |                                                   | int     | Gets the number of recent messages in current mailbox                                                                         |
| getAlerts             |                                                   | array   | Returns all IMAP alert messages that have occurred                                                                            |
| getErrors             |                                                   | array   | Returns all of the IMAP errors that have occurred                                                                             |
| getLastError          |                                                   | string  | Gets the last IMAP error that occurred during this page request                                                               |
| expunge               |                                                   | bool    | Delete all messages marked for deletion                                                                                       |
| checkCurrentMailbox   |                                                   | object  | Check current mailbox                                                                                                         |

### [Message::class](src/IMAP/Message.php)
| Method         | Arguments                     | Return      | Description                            |
| -------------- | ----------------------------- | :---------: | -------------------------------------- |
| delete         |                               |             | Delete the current Message             |
| restore        |                               |             | Restore a deleted Message              |
| copy           | string $mailbox, int $options |             | Copy the current Messages to a mailbox |
| move           | string $mailbox, int $options |             | Move the current Messages to a mailbox |
| moveToFolder   | string $mailbox               |             | Move the Message into an other Folder  |
| hasTextBody    |                               |             | Check if the Message has a text body   |
| hasHTMLBody    |                               |             | Check if the Message has a html body   |
| getTextBody    |                               | string      | Get the Message text body              |
| getHTMLBody    |                               | string      | Get the Message html body              |
| getAttachments |                               | collection  | Get all message attachments            |

### [Folder::class](src/IMAP/Folder.php)
| Method        | Arguments                                               | Return  | Description                                    |
| ------------- | ------------------------------------------------------- | :-----: | ---------------------------------------------- |
| hasChildren   |                                                         | bool    | Determine if folder has children.              |
| setChildren   | array $children                                         | self    | Set children.                                  |
| getMessages   | string $criteria                                        | array   | Get messages.                                  |
| delete        |                                                         |         | Delete the current Mailbox                     |
| move          | string $mailbox                                         |         | Move or Rename the current Mailbox             |
| getStatus     | integer $options                                        | object  | Returns status information on a mailbox        |
| appendMessage | string $message, string $options, string $internal_date | bool    | Append a string message to the current mailbox |



## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email github@webklex.com instead of using the issue tracker.

## Credits

- [Webklex][link-author]
- All Contributors

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/Webklex/laravel-imap.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Webklex/laravel-imap/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/Webklex/laravel-imap.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Webklex/laravel-imap.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Webklex/laravel-imap.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Webklex/laravel-imap
[link-travis]: https://travis-ci.org/Webklex/laravel-imap
[link-scrutinizer]: https://scrutinizer-ci.com/g/Webklex/laravel-imap/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Webklex/laravel-imap
[link-downloads]: https://packagist.org/packages/Webklex/laravel-imap
[link-author]: https://github.com/webklex
