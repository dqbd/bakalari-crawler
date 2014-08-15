<?php
namespace Skolar;


class Dispatcher {    

    protected static $klein = null;

    /**
    * Zahájí a vytvoří jednotlivé stránky + error logging
    *
    * @param string[] $config Configurační json soubor
    **/

    public static function init($config) {

        register_shutdown_function(function() {
            if (connection_status() == 2) {
                echo json_encode((new \Skolar\Response())->setError("Timeout"));
            }
        });

        Configuration::setConfig($config);

        \dibi::connect(Configuration::get("database"));

        self::$klein = new \Klein\Klein();

        foreach (Configuration::get("handlers") as $handler => $desc) {
            foreach($desc["modules"] as $module) {

                $module["uri"] = (!empty($module["uri"])) ? $module["uri"] : "";

                $url = sprintf('%s/[%s:action]/?%s', dirname($_SERVER["PHP_SELF"]), $module["name"], $module["uri"]);

                self::$klein->respond($url, function($request, $response) {
                    $response->json(self::output($request));
                });
            }
        }
        // self::$klein->onError(function($router, $msg, $type, $exception) {
        //     $answer = new \Skolar\Response();
        //     $answer->setError("Server error", $exception->getMessage());
        //     $router->response()->json($answer);
        //     die();
        // });

        self::$klein->onHttpError(function($code, $router) {
            $answer = new \Skolar\Response();

            if($code == 404) {
                $answer->setError("Invalid request");
            } else {
                $answer->setError("Error: ". $code);
            }

            $router->response()->json($answer->setResult($code));
        });

        self::$klein->dispatch();

    }

    /**
    * Výstup jednotlivých modulů a validace jednotlivých vstupů
    *
    * @param string[] $request
    **/

    private static function output($request) {
        foreach(Configuration::get("handlers") as $handler => $desc) {
            if(($id = array_search($request->action, array_column($desc["modules"], "name"))) !== false) {

                $post_params = self::getParameters($request->body());

                //check if all POST parameters are met
                if(!empty($desc["required"]) || !empty($desc["modules"][$id]["required"])) {
                    $required = (is_array($desc["required"][0])) ? $desc["required"] : array($desc["required"][0]);
                    
                    //second required parameters for custom modules
                    if(isset($desc["modules"][$id]["required"]) && is_array($desc["modules"][$id]["required"]) && !empty($desc["modules"][$id]["required"])) {
                        $first_required = $required;
                        $second_required = (is_array($desc["modules"][$id]["required"][0])) ? $desc["modules"][$id]["required"] : array($desc["modules"][$id]["required"]);

                        $required = array();

                        foreach($second_required as $second_param) {
                            foreach($first_required as $first_param) {
                                $required[] = array_merge($first_param, $second_param);
                            }
                        }
                    }

                    //samotná validace
                    if(!empty($post_params)) { //neplýtvej čas.
                        foreach($required as $n => $params) {
                            if(count(array_intersect($params, array_keys($post_params), $params)) == count($params)) {
                                break;
                            } else if ($n+1 == count($required)) {
                                throw new \Exception("Parameters invalid");
                            }
                        }
                    } else {
                        throw new \Exception("Parameters invalid");
                    }
                }

                $parameters = $request->paramsNamed()->merge($post_params);

                $handler = self::createHandler($handler, $request->action, $parameters);

                return self::handleOutput($handler->output());
            }
        }

        throw new \Exception("Failed configuration");
    }

    private static function handleOutput($output) {
        if(is_array($output)) {
            if(count($output) == 1) {
                return reset($output);
            }

            $response = new \Skolar\Response();

            $data = array();
            foreach($output as $name => $content) {

                if($content->getStatus() != true) {
                    $response->setStatus(0);
                }

                $data[$name] = $content->getData();
                // $data = array_merge($data, );
            }


            $output = $response->setResult($data);

            
        }

        return $output;
    }

    private static function getParameters($content) {
        if(SKOLAR_LOCAL == true && !empty($_GET)) {
            return array_merge($_GET, $_POST);
        }

        if(!empty($content)) {
            $content = file_get_contents('php://input');
        }

        $result = json_decode($content, true);

        return (is_array($result)) ? $result: array();
    }

    public static function createHandler($name, $module, $parameters = array()) {
        $handler_name = sprintf('\\%s\\Handlers\\%sHandler', __NAMESPACE__, ucfirst($name));

        if(class_exists($handler_name) == false) {
            throw new \Exception("Invalid request");
        }


        if(is_string($module)) {
            $module = self::createModule($module, $name, $parameters);
        }

        return new $handler_name($module, $parameters);
    }

    public static function createModule($name, $handler, $parameters = array()) {
        $module_name = sprintf('\\%s\\Modules\\%s\\%sModule', __NAMESPACE__, ucfirst($handler), ucfirst($name));

        return new $module_name($parameters);
    }
}

?>