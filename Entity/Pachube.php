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
    protected $feedId;

    public function __construct($apiVersion, $feedId, $baseUrl = null){
        //default url
        if (!isset($baseUrl))
            $this->baseUrl = "http://api.pachube.com/";
        //custom url
        else
            $this->baseUrl = $baseUrl;

        $this->setFeedId($feedId);

        $this->setApiVersion($apiVersion);

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
     * get feedId
     */
    public function getFeedId(){
        return $this->feedId;
    }

    /**
     * set feedId
     *
     * @param string $feedId
     */
    public function setFeedId($feedId){
        $this->feedId = $feedId;
    }


    public function buildUrl(){
        return $this->baseUrl . $this->getApiVersion() . "/feeds/" . $this->getFeedId() . "/datastreams/1";
    }
}
 
