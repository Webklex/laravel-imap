# Changelog

All notable changes to `webklex/laravel-imap` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [UNRELEASED]
### Fixed
- NaN

### Added
- NaN 

### Breaking changes
- NaN


## [4.0.0] - 2022-08-19
### Fixed
- PHP dependency updated to support php v8.0 (thanks @freescout-helpdesk)
- Method return and argument types added
- Imap `DONE` method refactored
- UID cache loop fixed
- `HasEvent::getEvent` return value set to mixed to allow multiple event types
- Protocol line reader changed to `fread` (stream_context timeout issue fixed)
- Issue setting the client timeout fixed
- IMAP Connection debugging improved
- `Folder::idle()` method reworked and several issues fixed
- Datetime conversion rules extended

### Breaking changes
- No longer supports php >=5.5.9 but instead requires at least php v7.0.0.
- `HasEvent::getEvent` returns a mixed result. Either an `Event` or a class string representing the event class.
- The error message, if the connection fails to read the next line, is now `empty response` instead of `failed to read - connection closed?`.
- The `$auto_reconnect` used with `Folder::indle()` is deprecated and doesn't serve any purpose anymore.


## [3.0.0-alpha] - 2021-11-10
### Fixed
- Debug line position fixed
- Handle incomplete address to string conversion
- Configured message key gets overwritten by the first fetched message
- Attachment::save() return error 'A facade root has not been set'
- Unused dependencies removed
- Fix PHP 8 error that changes null back in to an empty string. (thanks @mennovanhout)
- Fix regex to be case insensitive (thanks @mennovanhout)
- Attachment detection updated
- Timeout handling improved
- Additional utf-8 checks added to prevent decoding of unencoded values
- Boundary detection simplified
- Prevent potential body overwriting
- CSV files are no longer regarded as plain body
- Boundary detection overhauled to support "related" and "alternative" multipart messages
- Attachment saving filename fixed
- Unnecessary parameter removed from `Client::getTimeout()`
- Missing encryption variable added - could have caused problems with unencrypted communications
- Prefer attachment filename attribute over name attribute
- Missing connection settings added to `Folder:idle()` auto mode
- Message move / copy expect a folder path
- `Client::getFolder()` updated to circumvent special edge cases
- Missing connection status checks added to various methods
- Unused default attribute `message_no` removed from `Message::class`
- Fix setting default mask from config (thanks @shacky)
- Chunked fetch fails in case of less available mails than page size
- Protocol::createStream() exception information fixed
- Legacy methods (headers, content, flags) fixed
- Legacy connection cycle fixed (thanks @zssarkany)
- Several POP3 fixes (thanks @Korko)
- Fixes handling of long header lines which are seperated by `\r\n\t` (thanks @Oliver-Holz)
- Fixes to line parsing with multiple addresses (thanks @Oliver-Holz)
- Fixed problem with skipping last line of the response. (thanks @szymekjanaczek)
- Extend date parsing error message
- Fixed 'Where' method replaces the content with uppercase
- Don't surround numeric search values with quotes
- Context added to `InvalidWhereQueryCriteriaException`
- Redundant `stream_set_timeout()` removed

### Added
- Auto reconnect option added to `Folder::idle()`
- Dynamic Attribute access support added (e.g `$message->from[0]`)
- Message not found exception added
- Chunked fetching support added `Query::chunked()`. Just in case you can't fetch all messages at once
- "Soft fail" support added
- Count method added to `Attribute:class`
- Convert an Attribute instance into a Carbon date object
- Disable rfc822 header parsing via config option
- Added imap 4 handling. (thanks @szymekjanaczek)
- Added laravel's conditionable methods. (thanks @szymekjanaczek)
- Expose message folder path (thanks @Magiczne)
- Adds mailparse_rfc822_parse_addresses integration (thanks @Oliver-Holz)
- Added moveManyMessages method (thanks @Magiczne)
- Added copyManyMessages method (thanks @Magiczne)
- Added `UID` as available search criteria (thanks @szymekjanaczek)
- Make boundary regex configurable (thanks @EthraZa)
- IMAP ID support added
- Enable debug mode via config
- Custom UID alternative support added
- Fetch additional extensions using `Folder::query(["FEATURE_NAME"])`
- Optionally move a message during "deletion" instead of just "flagging" it (thanks @EthraZa)
- `WhereQuery::where()` accepts now a wide range of criteria / values.

### Breaking changes
- A new exception can occur if a message can't be fetched (`\Webklex\PHPIMAP\Exceptions\MessageNotFoundException::class`)
- `Message::move()` and `Message::copy()` no longer accept folder names as folder path
- A `Message::class` instance might no longer have a `message_no` attribute
- All protocol methods which had a `boolean` `$uid` option no longer support a boolean. Use `IMAP::ST_UID` or `IMAP::NIL` instead. If you want to use an alternative to `UID` just use the string instead.
- Default config option `options.sequence` changed from `IMAP::ST_MSGN` to `IMAP::ST_UID`.
- `Folder::query()` no longer accepts a charset string. It has been replaced by an extension array, which provides the ability to automatically fetch additional features.


## [2.4.0] - 2021-01-09
### Fixed
- Attachment::save() return error 'A facade root has not been set'
- Unused dependencies removed
- Fix PHP 8 error that changes null back in to an empty string. (thanks @mennovanhout)
- Fix regex to be case insensitive (thanks @mennovanhout)
- Debug line position fixed
- Handle incomplete address to string conversion
- Configured message key gets overwritten by the first fetched message
- Get partial overview when `IMAP::ST_UID` is set
- Unnecessary "'" removed from address names
- Folder referral typo fixed
- Legacy protocol fixed
- Treat message collection keys always as strings
- Missing RFC attributes added 
- Set the message sequence when idling
- Missing UID commands added

### Added
- Configurable supported default flags added
- Message attribute class added to unify value handling
- Address class added and integrated
- Alias `Message::attachments()` for `Message::getAttachments()` added
- Alias `Message::addFlag()` for `Message::setFlag()` added
- Alias `Message::removeFlag()` for `Message::unsetFlag()` added
- Alias `Message::flags()` for `Message::getFlags()` added
- New Exception `MessageFlagException::class` added
- New method `Message::setSequenceId($id)` added 
- Optional Header attributizion option added
- Get a message by its message number 
- Get a message by its uid

### Breaking changes
- Stringified message headers are now separated by ", " instead of " ". 
- All message header values such as subject, message_id, from, to, etc now consists of an `Àttribute::class` instance (should behave the same way as before, but might cause some problem in certain edge cases)
- The formal address object "from", "to", etc now consists of an `Address::class` instance  (should behave the same way as before, but might cause some problem in certain edge cases)
- When fetching or manipulating message flags a `MessageFlagException::class` exception can be thrown if a runtime error occurs
- Learn more about the new `Attribute` class here: [www.php-imap.com/api/attribute](https://www.php-imap.com/api/attribute)
- Learn more about the new `Address` class here: [www.php-imap.com/api/address](https://www.php-imap.com/api/address)
- Folder attribute "referal" is now called "referral"

## [2.3.0] - 2020-12-21
### Fixed
- Missing env variable `IMAP_AUTHENTICATION` added
- Header decoding problem fixed
- IMAP::FT_PEEK removing "Seen" flag issue fixed
- Text/Html body fetched as attachment if subtype is null
- Potential header overwriting through header extensions
- Prevent empty attachments
- Search performance increased by fetching all headers, bodies and flags at once
- Legacy protocol support updated
- Fix Query pagination. (thanks [@mikemiller891](https://github.com/mikemiller891))
- Missing array decoder method added (thanks [@lutchin](https://github.com/lutchin))
- Additional checks added to prevent message from getting marked as seen
- Boundary parsing improved (thanks [@AntonioDiPassio-AppSys](https://github.com/AntonioDiPassio-AppSys))
- Idle operation updated
- Cert validation issue fixed
- Allow boundaries ending with a space or semicolon (thanks [@smartilabs](https://github.com/smartilabs))
- Ignore IMAP DONE command response
- Default `options.fetch` set to `IMAP::FT_PEEK`
- Address parsing fixed
- Alternative rfc822 header parsing fixed
- Parse more than one header key
- Fetch folder overview fixed
- `Message::getTextBody()` fallback value fixed

### Added
- Default folder locations added
- Search for messages by message-Id
- Search for messages by In-Reply-To
- Message threading added `Message::thread()`
- Default folder locations added
- Set fetch order during query [@Max13](https://github.com/Max13)
- Missing message setter methods added
- `Folder::overview()` method added to fetch all headers of all messages in the current folder
- Force a folder to be opened
- Proxy support added 
- Flexible disposition support added
- New `options.message_key` option `uid` added
- Protocol UID support added
- Flexible sequence type support added

### Breaking changes
- Depending on your configuration, your certificates actually get checked. Which can cause an aborted connection if the certificate can not be validated.
- Messages don't get flagged as read unless you are using your own custom config.
- All `Header::class` attribute keys are now in a snake_format and no longer minus-separated.
- `Message::getTextBody()` no longer returns false if no text body is present. `null` is returned instead.


## [2.2.0] - 2020-10-16
### Fixed
- Prevent text bodies from being fetched as attachment
- Missing variable check added to prevent exception while parsing an address #356
- Missing variable check added to prevent exception while parsing a part subtype
- Missing variable check added to prevent exception while parsing a part content-type #356
- Mixed message header attribute `in_reply_to` "unified" to be always an array
- Potential message moving / copying problem fixed
- Move messages by using `Protocol::moveMessage()` instead of `Protocol::copyMessage()` and `Message::delete()`
- Boundary detection problem fixed ([@DasTobbel](https://github.com/DasTobbel))
- Content-Type detection problem fixed ([@DasTobbel](https://github.com/DasTobbel))
- If content disposition is multiline, implode the array to a simple string ([@DasTobbel](https://github.com/DasTobbel))
- Potential problematic prefixed white-spaces removed from header attributes
- Fix inline attachments and embedded images ([@dwalczyk](https://github.com/dwalczyk))
- Possible error during address decoding fixed ([@Slauta](https://github.com/Slauta))
- Flag event dispatching fixed
- Fixed `Query::paginate()` ([@Max13](https://github.com/Max13))
- `Message::getAttributes()` hasn't returned all parameters
- Wrong message content property reference fixed
- Fix header extension values
- Part header detection method changed
- Possible decoding problem fixed
- `Str::class` dependency removed from `Header::class`
- Dependency problem in `Attachement::getExtension()` fixed
- Quota handling fixed

### Added
- `Protocol::moveMessage()` method added
- Expended `Client::getFolder($name, $deleimiter = null)` to accept either a folder name or path ([@DasTobbel](https://github.com/DasTobbel))
- Special MS-Exchange header decoding support added
- `ClientManager::make()` method added to support undefined accounts
- Alternative attachment names support added ([@oneFoldSoftware](https://github.com/oneFoldSoftware))
- Fetch message content without leaving a "Seen" flag behind
- Support multiple boundaries ([@dwalczyk](https://github.com/dwalczyk))
- Part number added to attachment
- `Client::getFolderByPath()` added ([@Max13](https://github.com/Max13))
- `Client::getFolderByName()` added ([@Max13](https://github.com/Max13))
- Throws exceptions if the authentication fails  ([@Max13](https://github.com/Max13))
- Default account config fallback added

### Breaking changes
- Text bodies might no longer get fetched as attachment
- `Message::$in_reply_to` type changed from mixed to array


## [2.1.1] - 2020-10-15
### Fixed
- Missing default config parameter added #346

### Added
- Imap idle command added

## [2.1.0] - 2020-10-08
### Fixed
- Redundant class `ClientManager::class` removed and config handling moved to depending library "webklex/php-imap" #345 #344

### Breaking changes
- `\Webklex\IMAP\ClientManager::class` no longer exists. Please use the `\Webklex\IMAP\Facades\Client::class` facade or `\Webklex\PHPIMAP\ClientManager::class` instead. 

## [2.0.2] - 2020-09-23
### Fixed
- Missing default config parameter added (#337)

## [2.0.1] - 2020-09-22
### Fixed
- Wrong path to config directory (#336)

## [2.0.0] - 2020-09-22
### Fixed
- Encoding / Decoding improved and exception fallback added
- Missing pagination item records fixed (#287, #329)
- Missing attachments problem solved (#284, #277)

### Added 
- php-imap module replaced by direct socket communication
- Package core moved to `webklex/php-imap`
- Legacy protocol support added
- True IMAP IDLE support added (#185)
- oAuth support added (#180)
- Dynamic and customizable events added
- Fetching all available headers (#282)

### Breaking changes
- Most class namespaces have changed from `IMAP` to `PHPIMAP`
- Method response structure has changed in most classes
- Deprecated methods removed
- New exceptions can occur

### Affected Classes
- All

## [1.6.2] - 2020-09-07
### Fixed
- Exception handling improved and missing child exception added (#326)
- Fix 'A non well formed numeric value encountered' ErrorException (#327)

## [1.6.1] - 2020-09-04
### Fixed
- Greatly increased IDLE performance
- Message::fetchStructure() redundant code removed

### Added
- Read an overview of the information in the headers of a given message or sequence 
- Folder name encoding added (#324) (@aperture-it) 
- Add a fallback when aliasing encodings for iconv (#325) (@Hokan22) 
- Method to receive the encoded folder name added

### Affected Classes
- [Client::class](src/Client.php)
- [Message::class](src/Message.php)
- [Query::class](src/Query/Query.php)

## [1.6.0] - 2020-09-04
### Fixed
- Default fetch attributes set to `null` in order to use the config default value

### Added
- "BADCHARSET" exception will be used to determine the required charset (#100)

### Breaking changes
- Client::getMessages() default fetch parameter changed
- Folder::getMessages() default fetch parameter changed
- Folder::getMessage() default fetch parameter changed

### Affected Classes
- [Query::class](src/Query/Query.php)
- [Client::class](src/Client.php)
- [Folder::class](src/Folder.php)

## [1.5.3] - 2020-08-24
### Fixed
- Event parameter handling fixed (#322)

## [1.5.2] - 2020-08-24
### Added
- IDLE like support added (#185)

### Affected Classes
- [Query::class](src/Query/Query.php)

## [1.5.1] - 2020-08-23
### Added
- Message events added (deleted, restored, moved, new*) 

### Affected Classes
- [Message::class](src/Message.php)

## [1.5.0] - 2020-08-20
### Fixed
- Point to root namespace if handling native functions (#279)
- Use address charset from header information if set (#286)
- Fix Attachment::getExtension() under Laravel 7.x (#290) (@ThanhSonITNIC)
- Custom and X-Spam search criteria fixed (#296)

### Added
- Prevent double where condition strings
- Optional iconv_mime_decode() support added (#295)

### Breaking changes
- Custom search conditions have to begin with `CUSTOM `

### Affected Classes
- [Query::class](src/Query/WhereQuery.php)
- [Attachment::class](src/Attachment.php)
- [Client::class](src/Client.php)
- [Folder::class](src/Folder.php)
- [Message::class](src/Message.php)

## [1.4.5] - 2020-01-23
### Fixed
- Convert encoding of personal data struct #272
- Test & implement fix for #203 #270 #235

### Added
- Attachment size handling added #276
- Find messages by custom search criteria #268

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.4.4] - 2019-11-29
### Fixed
- Date handling in Folder::appendMessage() fixed #224
- Carbon Exception Parse Data #45
- Convert sender name from non-utf8 to uf8 #260 (@hwilok) #259

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Message::class](src/IMAP/Message.php)

## [1.4.3] - 2019-09-15
### Fixed
- .csv attachement is not processed #231
- mail part structure property comparison changed to lowercase #241 #242
- Replace helper functions for Laravel 6.0 #250 (@koenhoeijmakers)

### Added
- Path prefix option added to Client::getFolder() method #234

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)
- [Query::class](src/IMAP/Query/WhereQuery.php)
- [Mask::class](src/IMAP/Support/Masks/Mask.php)

## [1.4.2] - 2019-07-02
### Fixed
- Pagination count total bug #213
- Changed internal message move and copy methods #210
- Query::since() query returning empty response #215
- Carbon Exception Parse Data #45
- Reading a blank body (text / html) but only from this sender #203

### Added
- Message::getFolder() method
- Create a fast count method for queries #216
- STARTTLS encryption alias added

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Query::class](src/IMAP/Query/Query.php)
- [Client::class](src/IMAP/Client.php)

## [1.4.1] - 2019-04-13
### Fixed
- Problem with Message::moveToFolder() and multiple moves #31
- Problem with encoding conversion #203
- Message null value attribute problem fixed
- Client connection path handling changed to be handled inside the calling method #31

### Added
- Mailbox fetching exception added #201
- Message::moveToFolder() fetches new Message::class afterwards #31

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)

### Breaking changes
- Message::moveToFolder() returns either a Message::class instance or null and not a boolean

## [1.4.0] - 2019-03-18
### Fixed
- iconv(): error suppressor for //IGNORE added #184
- Typo Folder attribute fullName changed to full_name
- Query scope error fixed #153

### Added
- Message structure accessor added #182
- Shadow Imap const class added #188
- Connectable "NOT" queries added
- Additional where methods added
- Message attribute handling changed
- Attachment attribute handling changed
- Message flag handling updated
- Message::getHTMLBody($callback) extended
- Masks added (take look at the examples for more information on masks)
- More examples added
- Query::paginate() method added

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)
- [Query::class](src/IMAP/Query/Query.php)
- [WhereQuery::class](src/IMAP/Query/WhereQuery.php)

### Breaking changes
- Folder::fullName is now Folder::full_name
- Attachment::image_src might no longer work as expected - use Attachment::getImageSrc() instead

## [1.3.1] - 2019-03-12
### Fixed
- Replace embedded image with URL #151

### Added
- Imap client timeout can be modified and read #186
- Decoder config options added #175
- Message search criteria "NOT" added #181

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.3.0] - 2019-03-11
### Fixed
- Fix sender name in non-latin emails sent from Gmail (#155)
- Fix broken non-latin characters in body in ASCII (us-ascii) charset #156
- Message::getMessageId() returns wrong value #197
- Message date validation extended #45 #192

### Added
- Invalid message date exception added 

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.2.9] - 2018-09-15
### Fixed
- Removed "-i" from "iso-8859-8-i" in Message::parseBody #146

### Added
- Blade examples

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.2.8] - 2018-08-06
### Fixed
- Folder delimiter check added #137

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)

## [1.2.7] - 2018-08-06
### Fixed
- Broken non-latin characters in subjects and attachments  #133

### Added
- Required php extensions added to composer.json

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.2.6] - 2018-08-04
### Fixed
- Message subjects and attachment  names will now be decoded with a guessed encoding #97 #107

### Added
- Expunge option added to critical imap operations

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.2.5] - 2018-07-30
### Fixed
- Fixing undefined index error if associative config array isn't properly filled #131

### Affected Classes
- [LaravelServiceProvider::class](src/IMAP/Providers/LaravelServiceProvider.php)

## [1.2.4] - 2018-07-26
### Fixed
- fetch_flags default set to true on all methods
- Missing fetch_flags attribute added

### Added
- Folder::query() aliases added
- Priority fetching added

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Message::class](src/IMAP/Message.php)
- [Query::class](src/IMAP/Query/Query.php)

## [1.2.3] - 2018-07-23
### Fixed
- Config loading fixed and moved to a custom solution
- Set Encryption type correctly #128
- Moving a message takes now a uid #127

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)

## [1.2.2] - 2018-07-22
### Fixed
- Don't set the charset if it isn't used - prevent strange outlook mail server errors #100
- Protocol option added -minor Fix #126

### Added
- Query extended with markAsRead() and leaveUnread() methods

### Affected Classes
- [Query::class](src/IMAP/Query/Query.php)
- [Client::class](src/IMAP/Client.php)

## [1.2.1] - 2018-07-22
### Added
- WhereQuery aliases for all where methods added

### Affected Classes
- [WhereQuery::class](src/IMAP/Query/WhereQuery.php)

## [1.2.0] - 2018-07-22
### Fixed
- Charset error fixed #109
- Potential imap_close() error fixed #118
- Plain text attachments have a content type of other/plain of text/plain #119
- Carbon Exception Parse Data #45 

### Added
- Protocol option added #124
- Message collection key option added
- Message collection sorting option added
- Search Query functionality added
- Flag collection added
- Search methods updated

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)
- [Query::class](src/IMAP/Query/Query.php) [WhereQuery::class](src/IMAP/Query/WhereQuery.php)

## [1.1.1] - 2018-05-04
### Fixed
- Force to add a space between criteria in search query, otherwise no messages are fetched. Thanks to @cent89

### Added
- Attachment::getMimeType() and Attachment::getExtension() added

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.1.0] - 2018-04-24
### Fixed
- Client::createFolder($name) fixed #91
- Versions will now follow basic **Semantic Versioning** guidelines (MAJOR.MINOR.PATCH) 

### Added
- Connection validation added
- Client::renameFolder($old_name, $new_name) and Client::deleteFolder($name) methods added #91
- Find the folder containing a message #92
- Change all incoming encodings to iconv() supported ones #94

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)

## [1.0.5.9] - 2018-04-15
### Added
- Handle Carbon instances in message search criteria #82
- $message->getRawBody() throws Exception #88
- Request: add getReferences method to Message class #83

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Message::class](src/IMAP/Message.php)

## [1.0.5.8] - 2018-04-08
### Added
- Specify provider name when publishing the config #80
- Enable package discovery #81

## [1.0.5.7] - 2018-04-04
### Fixed
- Added option for optional attachment download #76
- Added option for optional body download
- Renamed "fetch" parameters
- hasAttachment() method added

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.5.6] - 2018-04-03
### Fixed
- More explicit date validation statements
- Resolving getMessage is not returning the body of the message #75

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)

## [1.0.5.5] - 2018-03-28
### Fixed
- New validation rule for a new invalid date format added (Exception Parse Data #45) 
- Default config keys are now fixed (Confusing default configuration values #66)

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.5.4] - 2018-03-27
### Fixed
- Clear error stack before imap_close #72

### Affected Classes
- [Client::class](src/IMAP/Client.php)

## [1.0.5.3] - 2018-03-18
### Added
- FolderCollection::class added
- Comments updated

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Folder::class](src/IMAP/Folder.php)
- [FolderCollection::class](src/IMAP/Support/FolderCollection.php)

## [1.0.5.2] - 2018-03-18
### Added
- Attachment::save() method added
- Unnecessary methods declared deprecated

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.0.5.1] - 2018-03-16
### Added
- Message collection moved to Support
- Attachment collection added
- Attachment class added

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)
- [Attachment::class](src/IMAP/Attachment.php)
- [MessageCollection::class](src/IMAP/Support/MessageCollection.php)
- [AttachmentCollection::class](src/IMAP/Support/AttachmentCollection.php)

## [1.0.5.0] - 2018-03-16
### Added
- Message search method added
- Basic pagination added
- Prevent automatic body parsing (will be default within the next major version (2.x))
- Unified MessageCollection::class added
- Several small improvements and docs added
- Implementation of the "get raw body" pull request [#59](https://github.com/Webklex/laravel-imap/pull/59)
- Get a single message by uid

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)
- [Folder::class](src/IMAP/Folder.php)
- [MessageCollection::class](src/IMAP/Support/MessageCollection.php)
- [MessageSearchValidationException::class](src/IMAP/Exceptions/MessageSearchValidationException.php)

## [1.0.4.2] - 2018-03-15
### Added
- Support message delivery status [#47](https://github.com/Webklex/laravel-imap/pull/47)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.4.1] - 2018-02-14
### Added
- Enable support to get In-Reply-To property from Message header. [#56](https://github.com/Webklex/laravel-imap/pull/56)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.4.0] - 2018-01-28
### Added
- Set and unset flags added `$oMessage->setFlag(['Seen', 'Spam']) or $oMessage->unsetFlag('Spam')`
- Get raw header string `$oMessage->getHeader()`
- Get additional header information `$oMessage->getHeaderInfo()`

### Affected Classes
- [Message::class](src/IMAP/Message.php)

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
