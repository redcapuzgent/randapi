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


function checkToken(\redcapuzgent\Randapi\Randapi $randapi,stdClass $jsonObject):bool {
    if(property_exists($jsonObject,"token")){
        try {
            $projectId = $randapi->getProjectId();
            $token = db_real_escape_string($jsonObject->token);
            // check for project specific token and for super user token
            $tokenQuery = "SELECT 1 as ok
                FROM redcap_user_information i
                JOIN redcap_user_rights u on i.username = u.username
                WHERE u.api_token = '" . db_escape($token) . "'
                AND u.project_id = ".$randapi->getProjectId()."
                AND i.user_suspended_time is null 
                UNION
                SELECT 1 as ok
                FROM redcap_user_information
                WHERE api_token = '" . db_escape($token) . "'
                AND user_suspended_time IS NULL 
                AND super_user = 1";

            error_log($tokenQuery);

            $tokenQueryResult = $randapi->query($tokenQuery);
            return !is_null($tokenQueryResult->fetch_assoc());
        }catch(\Exception $e){
            throw new RandapiException("Could not check token status",500,$e);
        }
    }else{
        throw new RandapiException("Token property was not set");
    }
}

function handleRequest(\redcapuzgent\Randapi\Randapi $randapi,stdClass $jsonObject, string $jsonText):void{
    if(property_exists($jsonObject,"action")){
        switch($jsonObject->action){
            case "addRecordsToAllocationTable":
                handleAddAllocation($randapi,$jsonObject);
                echo json_encode("success");
                break;
            case "randomizeRecord":
                $foundAid = handleRandomization($randapi,$jsonObject);
                echo json_encode("$foundAid");
                break;
            default:
                throw new RandapiException("Invalid Action was specified");
        }
    }else{
        http_response_code(500);
        $exception = new RandapiException("Invalid jsonObject was posted: $jsonText");
        echo json_encode($exception);
    }
}

try{

    if ($_SERVER['REQUEST_METHOD'] == 'POST'){

        header('Content-Type: application/json');

        $jsonText = file_get_contents("php://input");
        $jsonObject = json_decode($jsonText, false);

        if(checkToken($module,$jsonObject)){
            handleRequest($module,$jsonObject,$jsonText);
        }else{
            error_log("incorrect token");
            throw new RandapiException("You don't have sufficient privileges to access this api.",500);
        }
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


