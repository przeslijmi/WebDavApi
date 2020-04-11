<?php declare(strict_types=1);

namespace Przeslijmi\WebDavApi;

use Exception;
use Przeslijmi\WebDavApi\WebDavApi;

/**
 * Curl generator to create WebDav requests.
 */
class Curl
{

    /**
     * Curl itself.
     *
     * @var resource
     */
    private $curl;

    /**
     * Parent WebDavApi object.
     *
     * @var resource
     */
    private $api;

    /**
     * Headers collection.
     *
     * @var string[]
     */
    private $headers = [];

    /**
     * Result contents.
     *
     * @var array
     */
    private $result;

    /**
     * Constructor.
     *
     * @param WebDavApi $api Parent WebDavApi object.
     *
     * @since v1.0
     */
    public function __construct(WebDavApi $api)
    {

        // Save.
        $this->api = $api;

        // Create curl.
        $this->curl = curl_init();

        // Define credentials.
        if ($this->api->hasLogin() === true) {
            curl_setopt($this->curl, CURLOPT_USERPWD, ( $this->api->getUser() . ':' . $this->api->getPassword() ));
        }

        // Define defaults.
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_POST, 1);
    }

    /**
     * Setter for CURL request.
     *
     * @param string $request Request to send.
     *
     * @since  v1.0
     * @return self
     */
    public function setRequest(string $request) : self
    {

        // Define.
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $request);

        return $this;
    }

    /**
     * Setter for CURL url.
     *
     * @param string $url Url to use.
     *
     * @since  v1.0
     * @return self
     */
    public function setUrl(string $url) : self
    {

        // Make proper.
        $url = str_replace(' ', '%20', $url);
        $url = str_replace('\\', '/', $url);

        // Define.
        curl_setopt($this->curl, CURLOPT_URL, $url);

        return $this;
    }

    /**
     * Setter for CURL postFields.
     *
     * @param string $postFields Post fields to send.
     *
     * @since  v1.0
     * @return self
     */
    public function setPostfields(string $postFields) : self
    {

        // Define.
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postFields);

        return $this;
    }

    /**
     * Setter for ignoring SSL by Curl.
     *
     * @param boolean $ignore Default true. Whether to ignore SSL or not.
     *
     * @since  v1.0
     * @return self
     */
    public function setIgnoreSsl(bool $ignore = true) : self
    {

        // Define.
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, (bool) ( ! $ignore ));

        return $this;
    }

    /**
     * Adds headers to send with call.
     *
     * @param string $header Contents of header.
     *
     * @since  v1.0
     * @return self
     */
    public function addHeader(string $header) : self
    {

        // Save.
        $this->headers[] = $header;

        return $this;
    }

    /**
     * Perform this curl call.
     *
     * @since  v1.0
     * @throws Exception When calling failed.
     * @return self
     */
    public function call() : self
    {

        // Add headers if were given.
        if (count($this->headers) > 0) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
        }

        // Create call.
        $this->result = curl_exec($this->curl);

        // Serve errors.
        if ($this->result === false) {

            // Lvd.
            $message  = 'Curl Error: ' . curl_error($this->curl) . '. ';
            $message .= 'With result: ' . var_export($this->result, true) . '.';

            throw new Exception($message);
        }

        return $this;
    }

    /**
     * Getter for result contents.
     *
     * @return mixed
     */
    public function getResult()
    {

        return $this->result;
    }
}
