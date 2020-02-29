# Przeslijmi WebDavApi

## Requirements

Php 7.3.

Curl.

## Usage example

```
// Create api.
$webDavApi = new WebDavApi('https://example.com/remote.php/webdav/');
$webDavApi->setLogin($user, $password);

// Perfrom operations.
$webDavApi->delete('DirectoryName/FileName.txt');
$webDavApi->delete('DirectoryName');
$webDavApi->createFolder('NewDirectoryName');
$webDavApi->uploadFile($localUri, 'NewDirectoryName/Image.jpg');
$webDavApi->moveFile('NewDirectoryName/Image.jpg', 'NewDirectoryName/ImageRenamed.jpg');
```

## How to connect certificate

See: https://stackoverflow.com/questions/28858351/php-ssl-certificate-error-unable-to-get-local-issuer-certificate

Cite:

1. Download the certificate bundle here https://curl.haxx.se/docs/caextract.html.
1. Put it somewhere on drive.
1. Enable `mod_ssl` in Apache and `php_openssl.dll` in php.ini.
1. Add these lines to your cert in `php.ini`:

```
curl.cainfo="C:/.../cacert.pem"
openssl.cafile="C:/.../cacert.pem"
```
