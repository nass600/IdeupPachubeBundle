<?php
/**
 * User: Ignacio Velázquez Gómez <ivelazquez85@gmail.com>
 * Date: 12/1/11
 * Time: 12:26 PM
 */
namespace Ideup\PachubeBundle\Model;

use Doctrine\ORM\EntityManager,
	Doctrine\Common\Util\Debug
//	Ideup\PachubeBundle\Entity\Feed;

;

require(__DIR__ . '/../vendor/pachube-api/PachubeAPI.php');

class FeedManager
{
    protected $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    public function getFeed($apiKey, $feedId){
        //create Pachube object
        $pachube = new \PachubeAPI($apiKey);
        //getting data
        $data = $pachube->getFeedData("http://api.pachube.com/v2/feeds/$feedId/datastreams/1");

        return $data;
    }
}
 
