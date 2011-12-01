<?php
/**
 * User: Ignacio Velázquez Gómez <ivelazquez85@gmail.com>
 * Date: 12/1/11
 * Time: 12:08 PM
 */

namespace Ideup\PachubeBundle\Entity;

class Pachube
{
    protected $baseUrl;
    protected $apiVersion;
    protected $apiKey;
    protected $headers;


    public function __construct($baseUrl = "", $apiVersion){
        //default url
        if ($baseUrl == "")
            $this->baseUrl = "http://api.pachube.com/$apiVersion/feeds/";
        //custom url
        else
            $this->baseUrl = $baseUrl;

        $this->headers = array();
    }

    /**
     * get baseUrl
     */
    public function getBaseUrl(){
        return $this->baseUrl;
    }

    /**
     * set baseUrl
     *
     * @param string $baseUrl
     */
    protected function setBaseUrl($baseUrl){
        $this->baseUrl = $baseUrl;
    }

    /**
     * get apiVersion
     */
    public function getApiVersion(){
        return $this->apiVersion;
    }

    /**
     * set apiVersion
     *
     * @param string $apiVersion
     */
    public function setApiVersion($apiVersion){
        $this->apiVersion = $apiVersion;
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
    protected function setApiKey($apiKey){
        $this->apiKey = $apiKey;
    }

    /**
     * get headers
     */
    public function getHeaders(){
        return $this->headers;
    }

    /**
     * set headers
     *
     * @param string $headers
     */
    protected function setHeaders(array $headers){
        $this->headers[] = $headers;
    }
}
 
