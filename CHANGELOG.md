# Changelog

All notable changes to `webklex/laravel-imap` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [UNRELEASED]
### Fixed
- Fix implode error in Client.php, beacause imap_errors() can return FALSE instand of array

### Added
- FT_UID changed to $this->options which references to `imap.options.fetch`

### Affected Classes
\Webklex\IMAP\Message

## [1.0.3.1] - 2017-09-05
### Added
- getConnection method added
- Using a bit more fail save uid / msgNo by calling imap_msgno()

### Affected Classes
\Webklex\IMAP\Client
\Webklex\IMAP\Message

## [1.0.3.0] - 2017-09-01
### Changes
- Carbon dependency removed

## [1.0.2.12] - 2017-08-27
### Added
- Fixing text attachment issue - overwrite mail body (thx to radicalloop)

### Affected Classes
\Webklex\IMAP\Message

## [1.0.2.11] - 2017-08-25
### Added
- Attachment disposition (special thanks to radicalloop)
- Missing method added to README.md

### Affected Classes
\Webklex\IMAP\Message

## [1.0.2.10] - 2017-08-11
### Added
- $fetch_option setter added

### Affected Classes
\Webklex\IMAP\Message

## [1.0.2.9] - 2017-07-12
### Added
- Merged configuration
- New config parameter added
- "Known issues" added to README.md
- Typo fixed

### Affected Classes
\Webklex\IMAP\Client
\Webklex\IMAP\Providers\LaravelServiceProvider

## [1.0.2.8] - 2017-06-25
### Added
- Message attribute is now case insensitive
- Readme file extended
- Changelog typo fixed

### Affected Classes
\Webklex\IMAP\Message


## [1.0.2.7] - 2017-04-23
### Added
- imap_fetchheader(): Bad message number - merged
- Changed the default options in imap_fetchbody function - merged
- Attachment handling fixed (Plain text files are no longer ignored)
- Optional config parameter added.
- Readme file extended

### Changes 
\Webklex\IMAP\Client
\Webklex\IMAP\Message
\Webklex\IMAP\Folder

## [1.0.2.3] - 2017-03-09
### Added
- Code commented
- A whole bunch of functions and features added. To many to mention all of them ;)
- Readme file extended

### Changes 
\Webklex\IMAP\Client
\Webklex\IMAP\Message
\Webklex\IMAP\Folder

## 0.0.1 - 2017-03-04
### Added
- new laravel-imap package
