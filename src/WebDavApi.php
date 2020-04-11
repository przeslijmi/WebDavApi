<?php declare(strict_types=1);

namespace Przeslijmi\WebDavApi;

use Przeslijmi\WebDavApi\Curl;
use Przeslijmi\WebDavApi\XmlParser;

/**
 * WebDavApi
 *
 * ## Usage example
 * ```
 * // Create api.
 * $webDavApi = new WebDavApi('https://example.com/remote.php/webdav/');
 * $webDavApi->setIgnoreSsl(true);
 * $webDavApi->($user, $password);
 *
 * // Perfrom operations.
 * $webDavApi->delete('DirectoryName/FileName.txt');
 * $webDavApi->delete('DirectoryName');
 * $webDavApi->createFolder('NewDirectoryName');
 * $webDavApi->uploadFile($localUri, 'NewDirectoryName/Image.jpg');
 * $webDavApi->moveFile('NewDirectoryName/Image.jpg', 'NewDirectoryName/ImageRenamed.jpg');
 * $webDavApi->readContents('NewDirectoryName/');
 *
 * // Perform operations after `readContents`.
 * $contents   = $webDavApi->getContents();
 * $isExisting = $webDavApi->doesContentExists('NewDirectoryName/Image');
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
     * Does SSL security has to be ignored?
     *
     * @var boolean
     */
    private $ignoreSsl = false;

    /**
     * Contents array filled by last call of `readContents`.
     *
     * @var array
     */
    private $contents = [];

    /**
     * Dictionary of props that are searched for while scanning contents.
     *
     * @var string[]
     */
    private $propsDict = [
        'd:resourcetype',
        'd:getcontenttype',
        'd:getlastmodified',
        'd:getcontentlength',
        'd:quota-used-bytes',
        'd:quota-available-bytes',
        'd:getetag',
    ];

    /**
     * Constructor.
     *
     * @param string       $url  URL of WebDav service.
     * @param integer|null $port Optional. Port to connect to.
     */
    public function __construct(string $url, ?int $port = null)
    {

        // Save.
        $this->url  = $url;
        $this->port = $port;
    }

    /**
     * Setter for ignoring SSL by Curl.
     *
     * @param boolean $ignore Default true. Whether to ignore SSL or not.
     *
     * @return self
     */
    public function setIgnoreSsl(bool $ignore = true) : self
    {

        // Save.
        $this->ignoreSsl = $ignore;

        return $this;
    }

    /**
     * Setter for credentials.
     *
     * @param string $user     User name to log in.
     * @param string $password User password to log in.
     *
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
     * @return boolean
     */
    public function hasLogin() : bool
    {

        return ( is_null($this->user) === false && is_null($this->password) === false );
    }

    /**
     * Getter for whole URL (including port and ending slash).
     *
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
     * @return string
     */
    public function getUser() : string
    {

        return $this->user;
    }

    /**
     * Getter for user password.
     *
     * @return string
     */
    public function getPassword() : string
    {

        return $this->password;
    }

    /**
     * Starts connection and reads contents of given dir. See `getContents()`.
     *
     * @param string $folderUri Optinal, empty. Uri of folder to be read from.
     *
     * @return Curl
     */
    public function readContents(string $folderUri = '') : Curl
    {

        // Clear contents.
        $this->contents = [];

        // Create and call request.
        $curl = new Curl($this);
        $curl->setIgnoreSsl($this->ignoreSsl);
        $curl->setRequest('PROPFIND');
        $curl->setUrl($this->getUrl() . $folderUri);
        $curl->call();

        // Convert to xml.
        $parser   = new XmlParser($curl->getResult());
        $xml      = $parser->getAsObject();
        $startDir = '';

        // Convert to nice listing.
        foreach ($xml->{'d:multistatus'}[0]->{'children'}->{'d:response'} as $id => $response) {

            // First element to be returned is just a version of `..` directory.
            // Ignore it - but take it's value to remove repeated path from rest of elements.
            if ((int) $id === 0) {
                $startDir = $response->{'children'}->{'d:href'}[0]->{'value'};
                continue;
            }

            // Create content.
            $content = [];

            // Define href.
            $content['fullHref'] = $response->{'children'}->{'d:href'}[0]->{'value'};
            $content['href']     = substr($content['fullHref'], strlen($startDir));

            // Work on props.
            $props = $response->{'children'}->{'d:propstat'}[0]->{'children'}->{'d:prop'}[0];
            $props = $props->{'children'};

            // Work on each prop from dictionary.
            foreach ($this->propsDict as $propName) {

                // No prop of this name - continue further.
                if (isset($props->{$propName}[0]->{'value'}) === false) {
                    continue;
                }

                // Prop exists? Then save it.
                $content[substr($propName, 2)] = $props->{$propName}[0]->{'value'};
            }

            // Define is it dir or file.
            if (isset($props->{'d:resourcetype'}[0]->{'children'}->{'d:collection'}) === true) {
                $content['isDir'] = true;
            } else {
                $content['isDir'] = false;
            }

            // Add this content to final list.
            $this->contents[] = $content;
        }//end foreach

        return $curl;
    }

    /**
     * Request PUT to upload file.
     *
     * @param string $localFileUri  Uri of local file to be uploaded.
     * @param string $remotefileUri Uri of remote file location to use for uploaded file.
     *
     * @return Curl
     */
    public function uploadFile(string $localFileUri, string $remotefileUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setIgnoreSsl($this->ignoreSsl);
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
     * @return Curl
     */
    public function delete(string $remotefileUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setIgnoreSsl($this->ignoreSsl);
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
     * @return Curl
     */
    public function moveFile(string $oldRemoteFileUri, string $newRemoteFileUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setIgnoreSsl($this->ignoreSsl);
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
     * @return Curl
     */
    public function createFolder(string $folderUri) : Curl
    {

        // Create and call request.
        $curl = new Curl($this);
        $curl->setIgnoreSsl($this->ignoreSsl);
        $curl->setRequest('MKCOL');
        $curl->setUrl($this->getUrl() . $folderUri);
        $curl->call();

        return $curl;
    }

    /**
     * Getter for contents array filled by last call of `readContents`.
     *
     * @return array
     */
    public function getContents() : array
    {

        return $this->contents;
    }

    /**
     * Returns `true`/`false` depending on existence of given content in contents.
     *
     * Call `readContents()` before calling this method.
     *
     * @param string $testContentHref Content HREF to be tested.
     *
     * @return boolean
     */
    public function doesContentExists(string $testContentHref) : bool
    {

        // Check.
        foreach ($this->contents as $content) {
            if ($content['href'] === $testContentHref) {
                return true;
            }
        }

        return false;
    }
}
