<?php
// callable using
// https://localhost/redcap_v8.10.2/ExternalModules/?NOAUTH&prefix=Randapi&page=api
// https://localhost/api/?type=module&prefix=Randapi&page=api&NOAUTH

use redcapuzgent\Randapi\model\RandapiException;

try{

    if ($_SERVER['REQUEST_METHOD'] == 'POST'){

        header('Content-Type: application/json');

        $jsonText = file_get_contents("php://input");
        $jsonObject = json_decode($jsonText, false);
        /**
         * @var $module \redcapuzgent\Randapi\Randapi
         */
        $module->handleRequest($jsonObject,$jsonText);
    }else{
        include('help.php');
    }

}catch(RandapiException $e){
    http_response_code(500);
    echo json_encode($e);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(new RandapiException("unexpected error",100,$e));
}
?>


