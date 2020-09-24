# IMAP Library for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]][link-license]
[![Build Status][ico-build]][link-scrutinizer] 
[![Code quality][ico-quality]][link-scrutinizer] 
[![Total Downloads][ico-downloads]][link-downloads]
[![Hits][ico-hits]][link-hits]


## Description
Laravel IMAP is an easy way to integrate both the native php-imap module and an extended custom imap protocol 
into your **Laravel** app. This enables your app to not only respond to new emails but also allows it to 
read and parse existing mails and much more. 

> If you want to use this library outside of Laravel, please head over to [webklex/php-imap](https://github.com/Webklex/php-imap)
> for a standalone version.


## Table of Contents
- [Documentations](#documentations)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Basic usage example](#basic-usage-example)
    - [Facade](#facade)
    - [View examples](#view-examples)
    - [Idle](#idle)
    - [oAuth](#oauth)
    - [Events](#events)
- [Support](#support)
- [Known issues](#known-issues)
- [Security](#security)
- [Credits](#credits)
- [Supporters](#supporters)
- [License](#license)


## Documentations
- Legacy (< v2.0.0): [legacy documentation](https://github.com/Webklex/laravel-imap/tree/1.6.2#table-of-contents)
- Core documentation: [webklex/php-imap](https://github.com/Webklex/php-imap)
- Wiki: [php-imap wiki](https://github.com/Webklex/php-imap/wiki)


## Installation
1.) Install the Laravel IMAP package by running the following command:
```shell
composer require webklex/laravel-imap
```

1.1.) If you are getting errors or having some other issue, please follow step 1. - 1.1 
[here](https://github.com/Webklex/php-imap#installation).

1.2.) If you are having trouble with v2.0.0, please go ahead and create a new issue and perhaps 
try the latest v1.6.2 version:
```shell
composer require webklex/laravel-imap:1.6.2
```

2.) If you're using Laravel >= 5.5, package discovery will configure the service provider and `Client` alias out of the box.
Otherwise, for Laravel <= 5.4, edit your `config/app.php` file and:
- add the following to the `providers` array:
```php
Webklex\IMAP\Providers\LaravelServiceProvider::class,
```
- add the following to the `aliases` array: 
```php
'Client' => Webklex\IMAP\Facades\Client::class,
```

3.) Run the command below to publish the package config file [config/imap.php](src/config/imap.php):
```shell
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

Please see [webklex/php-imap#Configuration](https://github.com/Webklex/php-imap#configuration) and 
[config/imap.php](src/config/imap.php) for a detailed list of all available config options.


## Usage
#### Basic usage example
This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not be
tested in real life and is only meant to gives an impression on how things work.

```php
use Webklex\PHPIMAP\Client;

$client = new Client([
    'host'          => 'somehost.com',
    'port'          => 993,
    'encryption'    => 'ssl',
    'validate_cert' => true,
    'username'      => 'username',
    'password'      => 'password',
    'protocol'      => 'imap'
]);
/* Alternative by using the Facade
$client = Webklex\IMAP\Facades\Client::account('default');
*/

//Connect to the IMAP Server
$client->connect();

//Get all Mailboxes
/** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
$folders = $oClient->getFolders();

//Loop through every Mailbox
/** @var \Webklex\PHPIMAP\Folder $folder */
foreach($folders as $folder){

    //Get all Messages of the current Mailbox $folder
    /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
    $messages = $folder->messages()->all()->get();
    
    /** @var \Webklex\PHPIMAP\Message $message */
    foreach($messages as $message){
        echo $message->getSubject().'<br />';
        echo 'Attachments: '.$message->getAttachments()->count().'<br />';
        echo $message->getHTMLBody();
        
        //Move the current Message to 'INBOX.read'
        if($message->moveToFolder('INBOX.read') == true){
            echo 'Message has ben moved';
        }else{
            echo 'Message could not be moved';
        }
    }
}
```
Please see [webklex/php-imap#Table of Contents](https://github.com/Webklex/php-imap#table-of-contents) for more detail
and further examples.


#### Facade
If you use the Facade [\Webklex\IMAP\Facades\Client::class](src/IMAP/Facades/Client.php),
please start by selecting an in [config/imap.php](src/config/imap.php) defined account first followed by 
`Client::connect()` to establish an authenticated connection:

```php
use Webklex\IMAP\Facades\Client;

/** @var \Webklex\PHPIMAP\Client $client */
$client = Client::account('default');
$client->connect();
```

#### View examples
You can find a few blade and [mask](https://github.com/Webklex/php-imap#masking) examples under [/examples](examples).


#### Idle
Every time a new message is received, the server will notify the client and return the new message.

The callback and `Webklex\IMAP\Events\MessageNewEvent($message)` event get fired by every new incoming email.

```php
$timeout = 1200;
/** @var \Webklex\PHPIMAP\Folder $folder */
$folder->idle(function($message){
    /** @var \Webklex\PHPIMAP\Message $message */
    dump("new message", $message->subject);
}, $timeout);
```

#### oAuth
If you are using google mail or something similar, you might have to use oauth instead:

```php
use Webklex\PHPIMAP\Client;

/** @var \Webklex\PHPIMAP\Client $client */
$client = new Client([
    'host' => 'imap.gmail.com',
    'port' => 993,
    'encryption' => 'ssl',
    'validate_cert' => true,
    'username' => 'example@gmail.com',
    'password' => 'PASSWORD',
    'authentication' => "oauth",
    'protocol' => 'imap'
]);

//Connect to the IMAP Server
$oClient->connect();
```

#### Events
The following events are available:
- `Webklex\IMAP\Events\MessageNewEvent($message)` &mdash; can get triggered by `Folder::idle`
- `Webklex\IMAP\Events\MessageDeletedEvent($message)` &mdash; triggered by `Message::delete`
- `Webklex\IMAP\Events\MessageRestoredEvent($message)` &mdash; triggered by `Message::restore`
- `Webklex\IMAP\Events\MessageMovedEvent($old_message, $new_message)` &mdash; triggered by `Message::move`
- `Webklex\IMAP\Events\MessageCopiedEvent($old_message, $new_message)` &mdash; triggered by `Message::copy`
- `Webklex\IMAP\Events\FlagNewEvent($flag)` &mdash; triggered by `Message::setFlag`
- `Webklex\IMAP\Events\FlagDeletedEvent($flag)` &mdash; triggered by `Message::unsetFlag`
- `Webklex\IMAP\Events\FolderNewEvent($folder)` &mdash; can get triggered by `Client::createFolder`
- `Webklex\IMAP\Events\FolderDeletedEvent($folder)` &mdash; triggered by `Folder::delete`
- `Webklex\IMAP\Events\FolderMovedEvent($old_folder, $new_folder)` &mdash; triggered by `Folder::move`

Additional integration information:
- https://laravel.com/docs/7.x/events#event-subscribers
- https://laravel.com/docs/5.2/events#event-subscribers
- https://github.com/Webklex/php-imap#events

## Support
If you encounter any problems or if you find a bug, please don't hesitate to create a new 
[issue](https://github.com/Webklex/laravel-imap/issues).
However please be aware that it might take some time to get an answer.

Off topic, rude or abusive issues will be deleted without any notice.

If you need **immediate** or **commercial** support, feel free to send me a mail at github@webklex.com. 

##### A little notice
If you write source code in your issue, please consider to format it correctly. This makes it so much nicer to read 
and people are more likely to comment and help :)

&#96;&#96;&#96;php

echo 'your php code...';

&#96;&#96;&#96;

will turn into:
```php
echo 'your php code...';
```

### Features & pull requests
Everyone can contribute to this project. Every pull request will be considered but it can also happen to be declined. 
To prevent unnecessary work, please consider to create a [feature issue](https://github.com/Webklex/laravel-imap/issues/new?template=feature_request.md) 
first, if you're planning to do bigger changes. Of course you can also create a new [feature issue](https://github.com/Webklex/laravel-imap/issues/new?template=feature_request.md)
if you're just wishing a feature ;)

### Known issues
| Error                                                                     | Solution                                                   |
| ------------------------------------------------------------------------- | ---------------------------------------------------------- |
| Kerberos error: No credentials cache file found (try running kinit) (...) | Uncomment "DISABLE_AUTHENTICATOR" inside and use the `legacy-imap` protocol `config/imap.php` | 

## Change log
Please see [CHANGELOG][link-changelog] for more information what has changed recently.

## Security
If you discover any security related issues, please email github@webklex.com instead of using the issue tracker.

## Credits
- [Webklex][link-author]
- [All Contributors][link-contributors]

## Supporters
A special thanks to Jetbrains for supporting this project through their [open source license program](https://www.jetbrains.com/buy/opensource/).

[![Jetbrains][png-jetbrains]][link-jetbrains]

## License
The MIT License (MIT). Please see [License File][link-license] for more information.

[ico-version]: https://img.shields.io/packagist/v/webklex/laravel-imap.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Webklex/laravel-imap/master.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Webklex/laravel-imap.svg?style=flat-square
[ico-hits]: https://hits.webklex.com/svg/webklex/laravel-imap
[ico-build]: https://img.shields.io/scrutinizer/build/g/Webklex/laravel-imap/master?style=flat-square
[ico-quality]: https://img.shields.io/scrutinizer/quality/g/Webklex/laravel-imap/master?style=flat-square
[png-jetbrains]: https://www.webklex.com/jetbrains.png

[link-packagist]: https://packagist.org/packages/Webklex/laravel-imap
[link-travis]: https://travis-ci.org/Webklex/laravel-imap
[link-downloads]: https://packagist.org/packages/Webklex/laravel-imap
[link-scrutinizer]: https://scrutinizer-ci.com/g/Webklex/laravel-imap/?branch=master
[link-hits]: https://hits.webklex.com
[link-author]: https://github.com/webklex
[link-contributors]: https://github.com/Webklex/laravel-imap/graphs/contributors
[link-license]: https://github.com/Webklex/laravel-imap/blob/master/LICENSE
[link-changelog]: https://github.com/Webklex/laravel-imap/blob/master/CHANGELOG.md
[link-jetbrains]: https://www.jetbrains.com
