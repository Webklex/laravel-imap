# Changelog

All notable changes to `webklex/laravel-imap` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [UNRELEASED]

## [1.0.2.8] - 2017-06-25
### Added
-Message attribute is now case insensitive
-Readme file extended
-Changelog typo fixed

### Affected Classes
\Webklex\IMAP\Message


## [1.0.2.7] - 2017-04-23
### Added
-imap_fetchheader(): Bad message number - merged
-Changed the default options in imap_fetchbody function - merged
-Attachment handling fixed (Plain text files are no longer ignored)
-Optional config parameter added.
-Readme file extended

### Changes 
\Webklex\IMAP\Client
\Webklex\IMAP\Message
\Webklex\IMAP\Folder


## [1.0.2.3] - 2017-03-09
### Added
-Code commented
-A whole bunch of functions and features added. To many to mention all of them ;)
-Readme file extended

### Changes 
\Webklex\IMAP\Client
\Webklex\IMAP\Message
\Webklex\IMAP\Folder

## 0.0.1 - 2017-03-04
### Added
- new laravel-imap package
