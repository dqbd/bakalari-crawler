<?php

require __DIR__ . "/vendor/autoload.php";

$config = json_decode(file_get_contents("config.json"), true);
$methods = implode("|", \BakaParser\Utils::unflattenArray($config));

\dibi::connect(array(
    "driver" => "mysqli",
    "user" => "root",
    "pass" => "ivanagroskova",
    "database" => "skolar"
));

$klein = new \Klein\Klein();

//TODO: nějaký lepší způsob jak implementovat tohle
$klein->respond(\dirname($_SERVER['PHP_SELF']) . "/[" . $methods . ":action]/?", function($request, $response) use ($klein, $config) {

    // $klein->onError(function() {
    //     $response = new \BakaParser\Response();
    //     echo json_encode($response->setError("Server error", func_get_arg(3)->getMessage()));
    //     die();
    // });
    

    $response->json((new \BakaParser\Handler())->output($request, $config));
    
});

$klein->dispatch();
?>