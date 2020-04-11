# Przeslijmi WebDavApi

[![Run Status](https://api.shippable.com/projects/5e4da235124ede0007d6b891/badge?branch=master)]()

[![Coverage Badge](https://api.shippable.com/projects/5e4da235124ede0007d6b891/coverageBadge?branch=master)]()


## Requirements

Only **Php 7.3** and **Curl**. No other dependencies.


## Usage example

```php
// Create api.
$webDavApi = new WebDavApi('https://example.com/remote.php/webdav/');
$webDavApi->setLogin($user, $password);

// Perfrom operations.
$webDavApi->delete('DirectoryName/FileName.txt');
$webDavApi->delete('DirectoryName');
$webDavApi->createFolder('NewDirectoryName');
$webDavApi->uploadFile($localUri, 'NewDirectoryName/Image.jpg');
$webDavApi->moveFile('NewDirectoryName/Image.jpg', 'NewDirectoryName/ImageRenamed.jpg');
$webDavApi->readContents('NewDirectoryName/');

// Perform operations after `readContents`.
$contents   = $webDavApi->getContents();
$isExisting = $webDavApi->doesContentExists('NewDirectoryName/Image');
```


## How to connect certificate

See: https://stackoverflow.com/questions/28858351/php-ssl-certificate-error-unable-to-get-local-issuer-certificate

Cite:

1. Download the certificate bundle here https://curl.haxx.se/docs/caextract.html (or get it from `resurces` subdir).
1. Put it somewhere on drive.
1. Enable `mod_ssl` in Apache and `php_openssl.dll` in `php.ini` file.
1. Add these lines to your cert in `php.ini`:

```
curl.cainfo="C:/.../cacert.pem"
openssl.cafile="C:/.../cacert.pem"
```

## How to workaround SSL problem

Use:

```php
$webDavApi->setIgnoreSsl(true)
```

**BEWARE!** This turns SSL validation off.
