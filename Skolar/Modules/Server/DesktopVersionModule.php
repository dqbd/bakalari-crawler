<?php
// TODO: Až bude k dispozici desktop verze, znovu naimplement


// /*
//  * To change this license header, choose License Headers in Project Properties.
//  * To change this template file, choose Tools | Templates
//  * and open the template in the editor.
//  */

// namespace Skolar\Modules\Server;

// /**
//  * Description of VersionModule
//  *
//  * @author David
//  */
// class VersionModule extends \Skolar\Modules\BaseModule {
//     public function parse($request) {
        
//         if(empty($request["updater"])) {
//             return $this->response->setError("Missing updater version");
//         } else if ($request["updater"] != "v1") {
//             return $this->response->setError("Old version");
//         } else {
        
//             $result = array();
//             $result["versions"] = $this->getAllVersions();

//             if(!empty($request["current"])) {
//                 $version = $this->getIDfromVersion($request["current"], $result["versions"]);

//                 $keys = array_keys($result["versions"]);
//                 $result["updated"] = ($version >= end($keys));
//             } 



//             return $this->response->setResult($result);
//         }
//     }
    
//     //TODO: Upravit tak, aby respektoval i debug verze
//     private function getIDfromVersion($version, $versions) {
        
//         $return = -1;
//         foreach($versions as $key => $info) {
//             if($info["version"] == $version) {
//                 $return = $key;
//                 break;
//             }
//         }
        
//         return $return;

//     }
    
//     private function getAllVersions() {
//         $path = \Skolar\Utils::multiDirname(
//                 \Skolar\Utils::normalizeSlashes(__DIR__), 3) 
//                 ."/builds/builds.json";
        
//         $versions = json_decode(file_get_contents($path), true);
//         ksort($versions);
        
//         return $versions;   
//     }


//     public function getParameters($request = null) {
//         return $this->parameters;
//     }
// }

?>