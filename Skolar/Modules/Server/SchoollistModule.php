<?php

namespace Skolar\Modules\Server;

/**
 * AJAX request for schoollist
 *
 * @author David
 */
class SchoollistModule extends \Skolar\Modules\BaseModule {

    
    public function parse($content = null) {

        list($toplat, $toplng) = explode(",", $this->getRequestParam("northeast"));
        list($bottomlat, $bottomlng) = explode(",", $this->getRequestParam("southwest"));

        $stredlat = min($toplat, $bottomlat) + (abs($toplat - $bottomlat) / 2);
        $stredlng = min($toplng, $bottomlng) + (abs($toplng - $bottomlng) / 2);

        $limit = (is_numeric($this->getRequestParam("limit")) && $this->getRequestParam("limit") <= 60 && $this->getRequestParam("limit") >= 0) ? $this->getRequestParam("limit") : 60;

        $data = \dibi::query("SELECT id, name, url, address, latitude, longitude, (6378.10 * ACOS(COS(RADIANS(stredlat)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(stredlong)) + SIN(RADIANS(stredlat)) * SIN(RADIANS(latitude)))) AS distance 
        FROM [schoollist] 
        JOIN (SELECT %f", $stredlat, " AS stredlat, %f", $stredlng, " AS stredlong) as stred WHERE (latitude BETWEEN %f", min($toplat, $bottomlat), " AND %f", max($toplat, $bottomlat), ") AND (longitude BETWEEN %f", min($toplng, $bottomlng), " AND %f", max($toplng, $bottomlng), ") 
        HAVING distance < 20 
        ORDER BY distance 
        LIMIT 0, %i", $limit ,";")->fetchAll();

        return $this->response->setResult(array("schoollist" => $data));
    }

    public function getParameters($request = null) {
        return $this->parameters;
    }

}

?>
