<?php
/**
 * User: Ignacio Velázquez Gómez <ivelazquez85@gmail.com>
 * Date: 12/1/11
 * Time: 12:26 PM
 */
namespace Ideup\PachubeBundle\Model;

use Doctrine\ORM\EntityManager,
    Doctrine\Common\Util\Debug,
    Ideup\PachubeBundle\Entity\Pachube,
    Ideup\PachubeBundle\Connection\Connection,
    Ideup\PachubeBundle\Formatter\Formatter;

class PachubeManager
{
    protected $em;
    protected $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    /**
     * Creates a new feed
     *
     * @param null $title
     */
    public function createFeed($title = null){
        if ($this->conn->getApiKey() === null)
            $this->conn->exceptionHandler(Connection::WRONG_API);

        if ($title === null)
            $this->conn->exceptionHandler(Connection::MISSING_PARAMS);

        if(!function_exists('curl_init'))
            $this->conn->exceptionHandler(Connection::MISSING_CURL);

        $url = "http://api.pachube.com/api.xml";
        $eeml = "<eeml xmlns=\"http://www.eeml.org/xsd/005\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
            xsi:schemaLocation=\"http://www.eeml.org/xsd/005 http://www.eeml.org/xsd/005/005.xsd\"><environment>
            <title>$title</title></environment></eeml>";

        $this->conn->_curlCreate($url, $eeml);
    }

    /**
     * Reads feed
     *
     * @param string $apiVersion
     * @param string $apiKey
     * @param integer $feedId
     * @return DataTransform
     */
    public function readFeed($apiVersion, $feedId, $apiKey, $start = null, $end = null){
        if ($apiVersion != 'v1' && $apiVersion != 'v2')
            $this->conn->exceptionHandler(Connection::WRONG_API_VERSION);

        $this->conn->setApiKey($apiKey);

        if ($this->conn->getApiKey() === null)
            $this->conn->exceptionHandler(Connection::WRONG_API_KEY);


        //if historical query
        if ($start != null && $end != null){
            $startDate = new \DateTime($start);
            $endDate = new \DateTime($end);

            $next = clone $startDate;
            $interval = $startDate->diff($endDate);
            for ($i = 0; $i < $interval->days; $i++){
                $from = clone $next;
                $to = clone $next;
                $to->modify('+1 days');

                $pachube = new Pachube($apiVersion, $feedId);
                $pachube->setStartDate($from);
                $pachube->setEndDate($to);

                $url = $pachube->buildUrl();
//                var_dump($this->conn->_getRequest($url));
                $response = json_decode($this->conn->_getRequest($url));
                echo "<pre>";
                var_dump($response);
                echo "</pre>";
                die;
                $next->modify('+1 days');
            }
            die;
        }
        else{
            $pachube = new Pachube($apiVersion, $feedId);

            //building web service url
            $url = $pachube->buildUrl();

            //getting data
            $data = $this->conn->_getRequest($url);

        }

        return Formatter::toArray($data);
    }

    public function updateFeed(){

    }

    /**
     * Deletes the given feed
     *
     * @param string $feed_id
     */
    public function deleteFeed($feed_id=''){
        if ($this->conn->getApiKey() === null)
            $this->conn->exceptionHandler(Connection::WRONG_API);

        if(!is_numeric($feed_id))
            $this->conn->exceptionHandler(Connection::MISSING_PARAMS);

        $url = "http://api.pachube.com/api/feeds/".$feed_id;

        $this->conn->_curlDelete($url);
    }
}
 
