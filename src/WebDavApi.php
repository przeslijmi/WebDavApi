<?php declare(strict_types=1);

namespace Przeslijmi\WebDavApi;

use Przeslijmi\WebDavApi\Curl;

/**
 * WebDavApi
 *
 * ## Usage example
 * ```
 * // Create api.
 * $webDavApi = new WebDavApi('https://example.com/remote.php/webdav/');
 * $webDavApi->setLogin($user, $password);
 *
 * // Perfrom operations.
 * $webDavApi->delete('DirectoryName/FileName.txt');
 * $webDavApi->delete('DirectoryName');
 * $webDavApi->createFolder('NewDirectoryName');
 * $webDavApi->uploadFile($localUri, 'NewDirectoryName/Image.jpg');
 * $webDavApi->moveFile('NewDirectoryName/Image.jpg', 'NewDirectoryName/ImageRenamed.jpg');
 * ```
 */
class WebDavApi
{

    /**
     * URL of WebDav service.
     *
     * @var string
     */
    private $url;

    /**
     * Port to connect to.
     *
     * @var integer
     */
    private $port;

    /**
     * User name to log in.
     *
     * @var string
     */
    private $user;

    /**
     * User password to log in.
     *
     * @var string
     */
    private $password;

    /**
     * Constructor.
     *
     * @param string       $url  URL of WebDav service.
     * @param integer|null $port Optional. Port to connect to.
     *
     * @since v1.0
     */
    public function __construct(string $url, ?int $port = null)
    {

        // Save.
        $this->url  = $url;
        $this->port = $port;
    }

    /**
     * Setter for credentials.
     *
     * @param string $user     User name to log in.
     * @param string $password User password to log in.
     *
     * @since  v1.0
     * @return self
     */
    public function setLogin(string $user, string $password) : self
    {

        // Save.
        $this->user     = $user;
        $this->password = $password;

        return $this;
    }

    /**
     * Checker if API was given credentials.
     *
     * @since  v1.0
     * @return boolean
     */
    public function hasLogin() : bool
    {

        return ( is_null($this->user) === false && is_null($this->password) === false );
    }

    /**
     * Getter for whole URL (including port and ending slash).
     *
     * @since  v1.0
     * @return string
     */
    public function getUrl() : string
    {

        // Lvd.
        $result = rtrim($this->url, '/') . '/';

        // Add port if given.
        if ($this->port !== null) {
            $result .= ':' . $this->port;
        }

        return $result;
    }

    /**
     * Getter for user name.
     *
     * @since  v1.0
     * @return string
     */
    public function getUser() : string
    {

        return $this->user;
    }

    /**
     * Getter for user password.
     *
     * @since  v1.0
     * @return string
     */
    public function getPassword() : string
    {

        return $this->password;
    }

    /**
     * Request PUT to upload file.
     *
     * @param string $localFileUri  Uri of local file to be uploaded.
     * @param string $remotefileUri Uri of remote file location to use for uploaded file.
     *
     * @since  v1.0
     * @return Curl
     */
    public function uploadFile(string $localFileUri, string $remotefileUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setRequest('PUT');
        $curl->setUrl($this->getUrl() . $remotefileUri);
        $curl->setPostfields(file_get_contents($localFileUri));
        $curl->call();

        return $curl;
    }

    /**
     * Request delete file or folder from server.
     *
     * @param string $remotefileUri Uri of remote file location to be deleted.
     *
     * @since  v1.0
     * @return Curl
     */
    public function delete(string $remotefileUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setRequest('DELETE');
        $curl->setUrl($this->getUrl() . $remotefileUri);
        $curl->call();

        return $curl;
    }

    /**
     * Request move file on server (so also renaming).
     *
     * @param string $oldRemoteFileUri Old name of file (current).
     * @param string $newRemoteFileUri New name od file (to be changed to).
     *
     * @since  v1.0
     * @return Curl
     */
    public function moveFile(string $oldRemoteFileUri, string $newRemoteFileUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setRequest('MOVE');
        $curl->setUrl($this->getUrl() . $oldRemoteFileUri);
        $curl->addHeader('Destination: ' . $this->getUrl() . $newRemoteFileUri);
        $curl->call();

        return $curl;
    }

    /**
     * Request creating folder on server.
     *
     * @param string $folderUri Uri of folder to be created.
     *
     * @since  v1.0
     * @return Curl
     */
    public function createFolder(string $folderUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setRequest('MKCOL');
        $curl->setUrl($this->getUrl() . $folderUri);
        $curl->call();

        return $curl;
    }
}
