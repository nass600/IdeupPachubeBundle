<?php
/**
 * User: Ignacio Velázquez Gómez <ivelazquez85@gmail.com>
 * Date: 12/2/11
 * Time: 12:01 PM
 */

namespace Ideup\PachubeBundle\Transform;

class DataTransformer
{
    public static function toArray($rawData){
        return json_decode($rawData);
    }

    public static function toXML($rawData){
        null;
    }
}
