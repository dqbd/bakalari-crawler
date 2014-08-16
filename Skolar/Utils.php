<?php

namespace Skolar;

class Utils {
    
    /**
     * Zjednodušit množinu, flattern
     * 
     * @param array $array
     * @return array
     */
    public static function flattenArray($array) {
        $return = array();
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }
    
    /**
     * Provede dirname() na několik úrovní
     * 
     * @param string $input
     * @param int $level
     * @return string
     */
    public static function multiDirname($input, $level = 1) {
        for($i = 1; $i<=$level; $i++) {
            $input = dirname($input);
        }
        
        return $input;
    }
    
    /**
     * Normalizuje lomítka bez ohledu na OS
     * 
     * @param string $input
     * @return string
     */
    public static function normalizeSlashes($input) {
        return str_replace('\\', '/', $input);
    }

    /**
     * Opravit odkaz, naformátovat ho správně
     * 
     * @param type $input
     * @return type
     * @throws \InvalidArgumentException
     */
    public static function getBaseUrl($input) {
        $url = parse_url($input);

        if (empty($url["host"])) {
            throw new \InvalidArgumentException("Neplatný odkaz");
        }

        unset($url['query'], $url['fragment']);

        if (empty($url['scheme'])) {
            $url['scheme'] = "http";
        }

        if(!empty($url['path'])) {
            if (strpos($url["path"], ".") !== false) {
                $exploded = explode("/", $url["path"]);
                array_pop($exploded);
                $url['path'] = implode("/", $exploded);
            }

            $url['path'] .= "/";
        } else {
            $url['path'] = "/";
        }


        return self::unparse_url($url);
    }

    /**
     * Decode to URL
     * 
     * @param string[] $parsed_url
     * @return string
     */
    private static function unparse_url($parsed_url) {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

}

?>