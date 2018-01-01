# Changelog

All notable changes to `webklex/laravel-imap` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [UNRELEASED]
### Fixed
- NaN

### Added
- NaN

### Affected Classes
- NaN

## [1.0.3.11] - 2018-01-01
### Added
- New experimental function added [#48 How can I specify a single folder?](https://github.com/Webklex/laravel-imap/issues/48)

### Affected Classes
- [Client::class](src/IMAP/Client.php)

## [1.0.3.10] - 2018-01-01
### Fixed
- Ignore inconvertible chars in order to prevent sudden code exists

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.9] - 2017-12-03
### Fixed
- #45 DateTime::__construct(): Failed to parse time string (...)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.8] - 2017-11-24
### Fixed
- #41 imap_expunge(): supplied resource is not a valid imap resource
- #40 mb_convert_encoding(): Illegal character encoding specified

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.7] - 2017-11-05
### Fixed
- Fix assignment ```msgno``` to ```uid``` regardless of ```fetch_options``` is set in config 
- Disposition is checked in case of malformed mail attachments

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.6] - 2017-10-24
### Added
- A method to get only unread messages from email folders to [Client::class](src/IMAP/client.php)

## [1.0.3.5] - 2017-10-18
### Fixed
- Messageset issue resolved [#31](https://github.com/Webklex/laravel-imap/issues/31)

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.3.4] - 2017-10-04
### Fixed
- E-mails parsed without a content type of multipart present no body [#27](https://github.com/Webklex/laravel-imap/pull/27)
- Do not resolve uid to msgno if using FT_UID [#25](https://github.com/Webklex/laravel-imap/pull/25)

### Affected Classes
- [Message::class](src/IMAP/Message.php)


## [1.0.3.3] - 2017-09-22
### Fixed
- General code style and documentation

### Added
- several getter methods added to [Message::class](src/IMAP/Message.php)

### Affected Classes
- All

## [1.0.3.2] - 2017-09-07
### Fixed
- Fix implode error in Client.php, beacause imap_errors() can return FALSE instead of an array

### Added
- FT_UID changed to $this->options which references to `imap.options.fetch`

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.3.1] - 2017-09-05
### Added
- getConnection method added
- Using a bit more fail save uid / msgNo by calling imap_msgno()

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)

## [1.0.3.0] - 2017-09-01
### Changes
- Carbon dependency removed

## [1.0.2.12] - 2017-08-27
### Added
- Fixing text attachment issue - overwrite mail body (thx to radicalloop)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.2.11] - 2017-08-25
### Added
- Attachment disposition (special thanks to radicalloop)
- Missing method added to README.md

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.2.10] - 2017-08-11
### Added
- $fetch_option setter added

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.2.9] - 2017-07-12
### Added
- Merged configuration
- New config parameter added
- "Known issues" added to README.md
- Typo fixed

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [LaravelServiceProvider::class](src/IMAP/Providers/LaravelServiceProvider.php)

## [1.0.2.8] - 2017-06-25
### Added
- Message attribute is now case insensitive
- Readme file extended
- Changelog typo fixed

### Affected Classes
- [Message::class](src/IMAP/Message.php)


## [1.0.2.7] - 2017-04-23
### Added
- imap_fetchheader(): Bad message number - merged
- Changed the default options in imap_fetchbody function - merged
- Attachment handling fixed (Plain text files are no longer ignored)
- Optional config parameter added.
- Readme file extended

### Changes 
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)

## [1.0.2.3] - 2017-03-09
### Added
- Code commented
- A whole bunch of functions and features added. To many to mention all of them ;)
- Readme file extended

### Changes 
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)

## 0.0.1 - 2017-03-04
### Added
- new laravel-imap package
