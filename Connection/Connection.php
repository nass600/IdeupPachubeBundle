<?php
/**
 * User: Ignacio Velázquez Gómez <ivelazquez85@gmail.com>
 * Date: 12/2/11
 * Time: 9:12 AM
 */

namespace Ideup\PachubeBundle\Connection;

use Ideup\PachubeBundle\Entity\Pachube;

class Connection
{
    const WRONG_API = 401;
    const MISSING_PARAMS = 418;
    const MISSING_CURL = 500;

    protected $pachube;
    protected $apiKey;
    protected $header;

    /**
     * Create GET request to Pachube (wrapper)
     * @param string url
     * @return response
     */
    public function _getRequest($url)
    {
        if(function_exists('curl_init'))
            return $this->_curl($url,true);
        elseif(function_exists('file_get_contents') && ini_get('allow_url_fopen'))
            return $this->_get($url);
        else
            $this->exceptionHandler(Connection::MISSING_CURL);
    }

    /**
     * Create PUT request to Pachube (wrapper)
     * @param string url
     * @param string data
     * @return response
     */
    public function _putRequest($url, $data)
    {
        if(function_exists('curl_init')){
            $putData = tmpfile();
            fwrite($putData, $data);
            fseek($putData, 0);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
            curl_setopt($ch, CURLOPT_INFILE, $putData);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_exec($ch);
            $headers = curl_getinfo($ch);
            fclose($putData);
            curl_close($ch);

            return $headers['http_code'];
        }
        elseif(function_exists('file_put_contents') && ini_get('allow_url_fopen'))
            return $this->_put($url,$data);
        else
            $this->exceptionHandler(Connection::MISSING_CURL);
    }

    /**
     * cURL main function
     * @param string url
     * @param bool authentication
     * @return response
     */
    private function _curl($url, $auth=false)
    {
        if(function_exists('curl_init')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if($auth)
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
            $data = curl_exec($ch);
            //$headers = curl_getinfo($ch);
            curl_close($ch);
            return $data;
        }
        else
            return false;
    }

    public function _curlCreate($url, $eeml){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $eeml);

        $return = curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        $status = $headers['http_code'];

        if ($status != 201)
        {
            return $status;
        }
        else
        {
            return trim($this->_stringBetween($return,"Location: http://api.pachube.com/api/feeds/","\n"));
        }
    }

    public function _curlDelete($url){
        if(function_exists('curl_init'))
            $this->conn->exceptionHandler(Connection::MISSING_CURL);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->Pachube_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        return $headers['http_code'];
    }

    /**
     * GET requests to Pachube
     * @param string url
     * @return response
     */
    private function _get($url)
    {
        // Create a stream
        $opts['http']['method'] = "GET";
        $opts['http']['header'] = "X-PachubeApiKey: ".$this->apiKey."\r\n";
        $context = stream_context_create($opts);
        // Open the file using the HTTP headers set above
        return file_get_contents($url, false, $context);
    }

    /**
     * PUT requests to Pachube
     * @param string url
     * @param string data
     * @return response
     */
    private function _put($url,$data)
    {
        // Create a stream
        $opts['http']['method'] = "PUT";
        $opts['http']['header'] = "X-PachubeApiKey: ".$this->apiKey."\r\n";
        $opts['http']['header'] .= "Content-Length: " . strlen($data) . "\r\n";
        $opts['http']['content'] = $data;
        $context = stream_context_create($opts);
        // Open the file using the HTTP headers set above
        return file_get_contents($url, false, $context);
    }

    /**
     * get headers
     */
    public function getHeader(){
        return $this->header;
    }

    /**
     * set headers
     *
     * @param string $headers
     */
    protected function setHeader(array $headers){
        $this->header = $headers;
    }

    /**
     * get apiKey
     */
    public function getApiKey(){
        return $this->apiKey;
    }

    /**
     * set apiKey
     *
     * @param string $apiKey
     */
    public function setApiKey($apiKey){
        $this->apiKey = $apiKey;
        $this->setHeader(array("X-PachubeApiKey: $apiKey"));
    }


    public function exceptionHandler($statusCode){
        switch ($statusCode)
        {
            case 200:
                $msg = "Pachube feed successfully updated";
                break;
            case $this::WRONG_API:
                $msg = "Pachube API key was incorrect";
                break;
            case 404:
                $msg = "Feed ID or some other parameter does not exist";
                break;
            case 422:
                $msg = "Unprocessable Entity, semantic errors (CSV instead of XML?)";
                break;
            case $this::MISSING_PARAMS:
                $msg = "Error in feed ID, data type or some other data";
                break;
            case $this::MISSING_CURL:
                $msg = "cURL library not installed or some other internal error occured";
                break;
            default:
                $msg = "Status code not recognised: ".$statusCode;
                break;
        }
        throw new \Exception($msg);
    }
}
 
