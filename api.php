<?php
// callable using
// https://localhost/redcap_v8.10.2/ExternalModules/?NOAUTH&prefix=Randapi&page=api
// https://localhost/api/?type=module&prefix=Randapi&page=api&NOAUTH
require_once 'model/RandapiException.php';
require_once 'model/RandomizationAllocation.php';
require_once 'model/RandomizationField.php';

/**
 * @param $jsonObject
 * @throws RandapiException | Exception
 */
function handleAddAllocation(\redcapuzgent\Randapi\Randapi $randapi, $jsonObject){

    if(!property_exists($jsonObject,"parameters")){
        throw new RandapiException("parameters property not found.");
    }
    if(!property_exists($jsonObject->parameters, "projectId")){
        throw new RandapiException("parameters->projectId property not found.");
    }
    if(!is_numeric($jsonObject->parameters->projectId)){
        throw new RandapiException("parameters->projectId is not numeric.");
    }
    if(!property_exists($jsonObject->parameters, "project_status")){
        throw new RandapiException("parameters->project_status property not found.");
    }
    if(!is_numeric($jsonObject->parameters->project_status)){
        throw new RandapiException("parameters->project_status is not numeric.");
    }
    if(!in_array($jsonObject->parameters->project_status, array(0,1))){
        throw new RandapiException("parameters->project_status does not have values 0 or 1.");
    }
    if(!property_exists($jsonObject->parameters, "allocations")){
        throw new RandapiException("parameters->project_status property not found.");
    }
    if(!is_array($jsonObject->parameters->allocations)){
        throw new RandapiException("parameters->project_status is not an array.");
    }

    $allocations = array();
    foreach($jsonObject->parameters->allocations as $allocation){
        array_push($allocations,\redcapuzgent\Randapi\RandomizationAllocation::fromstdClass($allocation));
    }

    $randapi->addRecordsToAllocationTable($jsonObject->parameters->projectId,
        $jsonObject->parameters->project_status,
        $allocations);
}

/**
 * @param \redcapuzgent\Randapi\Randapi $randapi
 * @param $jsonObject
 * @return string
 * @throws RandapiException
 */
function handleRandomization(\redcapuzgent\Randapi\Randapi $randapi, $jsonObject){
    if(!property_exists($jsonObject,"parameters")){
        throw new RandapiException("parameters property not found.");
    }
    if(!property_exists($jsonObject->parameters, "recordId")){
        throw new RandapiException("parameters->recordId property not found.");
    }
    if(!property_exists($jsonObject->parameters, "projectId")){
        throw new RandapiException("parameters->projectId property not found.");
    }
    if(!is_numeric($jsonObject->parameters->projectId)){
        throw new RandapiException("parameters->projectId is not numeric.");
    }
    if(!property_exists($jsonObject->parameters, "fields")){
        throw new RandapiException("parameters->fields property not found.");
    }
    if(!property_exists($jsonObject->parameters, "resultFieldName")){
        throw new RandapiException("parameters->resultFieldName property not found.");
    }

    // optional
    $groupId = "";
    if(property_exists($jsonObject->parameters, "groupId")){
        $groupId = $jsonObject->parameters->groupId;
    }
    $armName = "Arm 1";
    if(property_exists($jsonObject->parameters, "armName")){
        $groupId = $jsonObject->parameters->armName;
    }
    $eventName = "Event 1";
    if(property_exists($jsonObject->parameters, "eventName")){
        $eventName = $jsonObject->parameters->eventName;
    }

    $fields = array();
    foreach($jsonObject->parameters->fields as $field){
        array_push($fields,\redcapuzgent\Randapi\RandomizationField::fromStdClass($field));
    }
    //randomizeRecord($recordId,$projectId,$fields=array(),$resultFieldName,$group_id='',$arm_name='Arm 1', $event_name='Event 1'){
    return $randapi->randomizeRecord($jsonObject->parameters->recordId,
        $jsonObject->parameters->projectId,
        $fields,
        $jsonObject->parameters->resultFieldName,
        $groupId,$armName,$eventName);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST'){

    header('Content-Type: application/json');

    $jsonText = file_get_contents("php://input");
    $jsonObject = json_decode($jsonText, false);

    if(property_exists($jsonObject,"action")){
        try{
            switch($jsonObject->action){
                case "addRecordsToAllocationTable":
                    handleAddAllocation($module,$jsonObject);
                    echo json_encode("success");
                    break;
                case "randomizeRecord":
                    $foundAid = handleRandomization($module,$jsonObject);
                    echo json_encode("$foundAid");
                    break;
                default:
                    http_response_code(500);
                    echo json_encode("incorrect action");
                    exit(0);
            }
        }catch(RandapiException $e){
            http_response_code(500);
            echo json_encode($e);
        }catch(Exception $e){
            http_response_code(500);
            echo json_encode(new RandapiException("unexpected error",100,$e));
        }
    }else{
        http_response_code(500);
        $exception = new RandapiException("Invalid jsonObject was posted: $jsonText");
        echo json_encode($exception);
    }
}else{
    include('help.php');
}
?>


