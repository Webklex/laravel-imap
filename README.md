# IMAP Library for Laravel

[![Latest release on Packagist][ico-release]][link-packagist]
[![Latest prerelease on Packagist][ico-prerelease]][link-packagist]
[![Software License][ico-license]][link-license]
[![Build Status][ico-travis]][link-travis]
[![Code quality][ico-quality]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![Hits][ico-hits]][link-hits]
[![Discord][ico-discord]][link-discord]
[![Snyk][ico-snyk]][link-snyk]


## Description
Laravel IMAP is an easy way to integrate both the native php-imap module and an extended custom imap protocol
into your **Laravel** app. This enables your app to not only respond to new emails but also allows it to
read and parse existing mails and much more.

Official documentation: [php-imap.com/frameworks/laravel](https://www.php-imap.com/frameworks/laravel/installation)

Discord: [discord.gg/jCcZWCSq][link-discord]

## Table of Contents
- [Documentations](#documentations)
- [Installation](#installation)
- [Basic usage example](#basic-usage-example)
- [Known issues](#known-issues)
- [Support](#support)
- [Features & pull requests](#features--pull-requests)
- [Security](#security)
- [Credits](#credits)
- [Supporters](#supporters)
- [License](#license)


## Documentations
- Legacy (< v2.0.0): [legacy documentation](https://github.com/Webklex/laravel-imap/tree/1.6.2#table-of-contents)
- Core documentation: [php-imap.com](https://www.php-imap.com/)


## Installation
This library requires the `mbstring` and `mcrypt` php module. Make sure to install or enable them if they arn't available.
```bash
sudo apt-get install php*-mbstring php*-mcrypt
```
Installation via composer:
```bash
composer require webklex/laravel-imap
```
Additional information such as troubleshooting, legacy support and package publishing can be found here: 
[php-imap.com/frameworks/laravel/installation](https://www.php-imap.com/frameworks/laravel/installation)

## Basic usage example
This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not be
tested in real life and is only meant to gives an impression on how things work.

```php
/** @var \Webklex\PHPIMAP\Client $client */
$client = Webklex\IMAP\Facades\Client::account('default');

//Connect to the IMAP Server
$client->connect();

//Get all Mailboxes
/** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
$folders = $client->getFolders();

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
        if($message->move('INBOX.read') == true){
            echo 'Message has ben moved';
        }else{
            echo 'Message could not be moved';
        }
    }
}
```

### Known issues
| Error                                                                     | Solution                                                   |
| ------------------------------------------------------------------------- | ---------------------------------------------------------- |
| Kerberos error: No credentials cache file found (try running kinit) (...) | Uncomment "DISABLE_AUTHENTICATOR" inside and use the `legacy-imap` protocol `config/imap.php` | 


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
To prevent unnecessary work, please consider to create a 
[feature issue](https://github.com/Webklex/laravel-imap/issues/new?template=feature_request.md)  
first, if you're planning to do bigger changes. Of course you can also create a new 
[feature issue](https://github.com/Webklex/laravel-imap/issues/new?template=feature_request.md)
if you're just wishing a feature ;)


## Change log
Please see [CHANGELOG][link-changelog] for more information what has changed recently.

## Security
If you discover any security related issues, please email github@webklex.com instead of using the issue tracker.

## Credits
- [Webklex][link-author]
- [All Contributors][link-contributors]

## License
The MIT License (MIT). Please see [License File][link-license] for more information.


[ico-release]: https://img.shields.io/packagist/v/webklex/laravel-imap.svg?style=flat-square&label=version
[ico-prerelease]: https://img.shields.io/github/v/release/webklex/laravel-imap?include_prereleases&style=flat-square&label=pre-release
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/Webklex/laravel-imap/master.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Webklex/laravel-imap.svg?style=flat-square
[ico-hits]: https://hits.webklex.com/svg/webklex/laravel-imap?
[ico-build]: https://img.shields.io/scrutinizer/build/g/Webklex/laravel-imap/master?style=flat-square
[ico-quality]: https://img.shields.io/scrutinizer/quality/g/Webklex/laravel-imap/master?style=flat-square
[ico-snyk]: https://snyk-widget.herokuapp.com/badge/composer/webklex/laravel-imap/badge.svg
[ico-discord]: https://img.shields.io/static/v1?label=discord&message=open&color=5865f2&style=flat-square

[link-packagist]: https://packagist.org/packages/Webklex/laravel-imap
[link-travis]: https://travis-ci.org/Webklex/laravel-imap
[link-downloads]: https://packagist.org/packages/Webklex/laravel-imap
[link-scrutinizer]: https://scrutinizer-ci.com/g/Webklex/laravel-imap/?branch=master
[link-hits]: https://hits.webklex.com
[link-author]: https://github.com/webklex
[link-contributors]: https://github.com/Webklex/laravel-imap/graphs/contributors
[link-license]: https://github.com/Webklex/laravel-imap/blob/master/LICENSE
[link-changelog]: https://github.com/Webklex/laravel-imap/blob/master/CHANGELOG.md
[link-snyk]: https://snyk.io/vuln/composer:webklex%2Flaravel-imap
[link-discord]: https://discord.gg/jCcZWCSq
