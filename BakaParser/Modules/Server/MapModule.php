<?php

namespace BakaParser\Modules\Server;

/**
 * AJAX request for schoollist
 *
 * @author David
 */
class MapModule extends \BakaParser\Modules\BaseModule {

    
    public function parse($request) {
        list($toplat, $toplng) = explode(",", $request["topleft"]);
        list($bottomlat, $bottomlng) = explode(",", $request["bottomright"]);

        $stredlat = min($toplat, $bottomlat) + (abs($toplat - $bottomlat) / 2);
        $stredlng = min($toplng, $bottomlng) + (abs($toplng - $bottomlng) / 2);

        $data = \dibi::query("SELECT id, name, url, address, latitude, longitude, (6378.10 * ACOS(COS(RADIANS(stredlat)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(stredlong)) + SIN(RADIANS(stredlat)) * SIN(RADIANS(latitude)))) AS distance 
        FROM [schoollist] 
        JOIN (SELECT %f", $stredlat, " AS stredlat, %f", $stredlng, " AS stredlong) as stred WHERE (latitude BETWEEN %f", min($toplat, $bottomlat), " AND %f", max($toplat, $bottomlat), ") AND (longitude BETWEEN %f", min($toplng, $bottomlng), " AND %f", max($toplng, $bottomlng), ") 
        HAVING distance < 20 
        ORDER BY distance 
        LIMIT 0, 30;")->fetchAll();
        
        return $this->response->setResult($data);
    }

    public function getParameters($request = null) {
        return $this->parameters;
    }

}

?>
