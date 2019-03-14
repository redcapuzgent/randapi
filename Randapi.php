<?php
namespace redcapuzgent\Randapi;

require_once __DIR__.DIRECTORY_SEPARATOR."vendor/autoload.php";

use \ExternalModules\AbstractExternalModule;
use \Exception;
use \Randomization;
use \stdClass;
use redcapuzgent\Randapi\model\RandomizationAllocation;
use redcapuzgent\Randapi\model\RandomizationField;
use redcapuzgent\Randapi\model\RandapiException;

class Randapi extends AbstractExternalModule
{

    public function __construct(){
        parent::__construct();
    }

    /**
     * @param string $recordId The record that we want to randomize
     * @param int $projectId The projectId where the record belongs to
     * @param RandomizationField[] $fields An array of RandomizationFields
     * @param string$resultFieldName The field where the randomization result can be stored.
     * @param string $group_id (optional) The DAG identifier. default = '' (none)
     * @param string $arm_name (optional) The name of the arm. default = 'Arm 1'
     * @param string $event_name (optional) The name of the event. default = 'Event 1'
     * @return string returns the field value result of the randomization
     * @throws Exception
     */
    function randomizeRecord(string $recordId,int $projectId,array $fields=array(),string $resultFieldName,string $group_id='',string $arm_name='Arm 1', string $event_name='Event 1'): string{
        // set globals required for  Randomization::getRandomizationFields;
        global $redcap_version;
        $classesPath = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."redcap_v$redcap_version".DIRECTORY_SEPARATOR."Classes".DIRECTORY_SEPARATOR;
        require_once($classesPath."Randomization.php");
        require_once(__DIR__ . DIRECTORY_SEPARATOR."/model/RandomizationField.php");

        if(!defined(PROJECT_ID)){
            error_log("defining project id $projectId");
            define(PROJECT_ID,$projectId);
        }else{
            error_log("project id was already defined");
        }
        global $longitudinal;
        if(!isset($longitudinal)){
            error_log("defining longitudinal");
            //echo "requiring $classesPath"."Project.php";
            require_once($classesPath."Project.php");
            $proj = new \Project($projectId, true);
            $longitudinal = $proj->longitudinal;
        }else{
            error_log("longitudinal was already defined");
        }
        // set globals required for Randomization::randomizeRecord()
        global $status;
        if(!isset($status)){
            error_log("defining status");
            Randomization::wasRecordRandomized($recordId);
        }else{
            error_log("status was already defined");
        }
        // calls getRandomizationFields
        $tfields = array();
        foreach($fields as $field) {
            $tfields[$field->getKey()] = $field->getValue();
        }
        error_log("randomizing using fields ".print_r($tfields,true));
        $aid =  Randomization::randomizeRecord($recordId,$tfields,$group_id);
        if($aid){
            // retrieve randomization value (target_field)

            // SQL Inject check
            // ----------------

            // $aid is a method result

            $query = "select target_field from redcap.redcap_randomization_allocation rra where rra.aid = $aid";

            $randomizationQueryResult = $this->query($query);
            $randomizationResult = false;
            if($row = $randomizationQueryResult->fetch_assoc()){
                $randomizationResult = $row["target_field"];
            }
            $randomizationQueryResult->close();
            if($randomizationResult){
                // The randomization result cannot be changed so an insert should not fail because of an duplicate row exception.
                // The API cannot be used to insert the randomization result

                // SQL Injection check
                // -------------------

                // $projectId is int in method signature
                // $recordId should be escaped
                $recordId = db_real_escape_string($recordId);
                // $resultFieldName should be escaped
                $resultFieldName = db_real_escape_string($resultFieldName);
                // $randomizationResult is not passed by a parameter
                // $event_name should be escaped
                $event_name = db_real_escape_string($event_name);
                // $arm_name should be escaped
                $arm_name = db_real_escape_string($arm_name);

                $query = "
                insert into redcap.redcap_data(project_id, event_id, record, field_name, `value`)
                select $projectId as project_id, md.event_id, '$recordId' as record, '$resultFieldName' as field_name, '$randomizationResult' as `value`
                from redcap.redcap_events_arms a
                join redcap.redcap_events_metadata md on 
                    a.arm_id = md.arm_id and
                    md.descrip = '$event_name'
                where a.project_id = 20 and
                    a.arm_name='$arm_name';";
                if($this->query($query)){
                    error_log("Randomization result $randomizationResult for aid $aid successfully saved in record");
                    return $randomizationResult;
                }else{
                    $msg = "Could not save randomization result $randomizationResult for aid $aid. ".mysqli_error($this->conn);
                    error_log($msg);
                    throw new Exception($msg);
                }
            }else {
                $msg = "No result found for aid $aid";
                error_log($msg);
                throw new Exception($msg);
            }
        }else{
            throw new Exception("Standard Randomization class could not randomize record");
        }
    }

    /**
     * @param int $projectId The project id
     * @param int $project_status (0 = development, 1 = production)
     * @param RandomizationAllocation[] $allocations
     * @throws Exception
     */
    public function addRecordsToAllocationTable(int $projectId,int $project_status,array $allocations): void{
        // SQL injection check
        // -------------------

        // $projectId is typed in method signature

        $ridQuery = "select rid from redcap.redcap_randomization where project_id = $projectId";
        $rid = false;
        try{
            if($ridQueryResult = $this->query($ridQuery)){
                if($row = $ridQueryResult->fetch_assoc()){
                    $rid = $row["rid"];
                }
                $ridQueryResult->close();
            }else{
                $msg = "Could not execute ridQuery $ridQuery. ";
                error_log($msg);
            }
        }catch(\Exception $e){
            $msg = "Could not execute ridQuery $ridQuery. ".$e->getMessage()." ".$e->getTraceAsString();
            error_log($msg);
        }
        if($rid) {

            foreach ($allocations as $allocation) {
                $sourceFieldNames = $allocation->getSourceFieldNames();

                // SQL Injection check
                // -------------------

                // $sourceFieldNames is generated here
                // $rid is a database result
                // $project_status is typed (int) in the method signature
                // $allocation->getTargetField() should be escaped
                $target_field = db_real_escape_string($allocation->getTargetField());
                // $allocation->getSourceFields() should be escaped
                $sourceFieldValues = $allocation->getSourceFieldValues();


                $query = "
                  insert into redcap_randomization_allocation(rid,project_status,target_field," . implode(',', $sourceFieldNames) . ")
                  values($rid,$project_status,'$target_field','" . implode("','", $sourceFieldValues) . "');";

                error_log("Executing query: $query");
                if (!$this->query($query)) {
                    throw new Exception("Could not add allocation values to table for query: $query. Exception: " . mysqli_error($this->conn));
                }
            }
        }else{
            throw new Exception("Could not find rid");
        }
    }

    /**
     * @param $jsonObject
     * @throws RandapiException | Exception
     */
    private function handleAddAllocation($jsonObject): void{

        if(!property_exists($jsonObject,"parameters")){
            throw new RandapiException("parameters property not found.");
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
            array_push($allocations,RandomizationAllocation::fromstdClass($allocation));
        }

        $this->addRecordsToAllocationTable($this->getProjectId(),
            $jsonObject->parameters->project_status,
            $allocations);
    }

    /**
     * @param $jsonObject
     * @return string
     * @throws \RandapiException
     */
    private function handleRandomization($jsonObject): string{
        if(!property_exists($jsonObject,"parameters")){
            throw new RandapiException("parameters property not found.");
        }
        if(!property_exists($jsonObject->parameters, "recordId")){
            throw new RandapiException("parameters->recordId property not found.");
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
            array_push($fields,RandomizationField::fromStdClass($field));
        }
        //randomizeRecord($recordId,$projectId,$fields=array(),$resultFieldName,$group_id='',$arm_name='Arm 1', $event_name='Event 1'){
        return $this->randomizeRecord($jsonObject->parameters->recordId,
            $this->getProjectId(),
            $fields,
            $jsonObject->parameters->resultFieldName,
            $groupId,$armName,$eventName);
    }

    /**
     * @param stdClass $jsonObject
     * @return int
     * @throws RandapiException
     */
    private function handleAvailableSlots(stdClass $jsonObject): int {
        if(!property_exists($jsonObject,"parameters")){
            throw new RandapiException("parameters property not found.");
        }
        if(!property_exists($jsonObject->parameters, "source_fields")){
            throw new RandapiException("parameters->source_fields property not found.");
        }

        /**
         * @var $source_fields string[]
         */
        $source_fields = $jsonObject->parameters->source_fields;

        $sourceWhere = array();
        for($i = 0; $i < sizeof($source_fields); $i++){
            array_push($sourceWhere,"rra.source_field".($i+1)." = '".$source_fields[$i]."'");
        }

        $asQuery = "select count(*) as nrOfAvailableSlots
                from redcap_randomization_allocation rra
                join redcap_randomization rr on 
                    rr.project_id = ".$this->getProjectId()." and
                    rr.rid = rra.rid
                where rra.is_used_by is null and
                        ".implode(" and ",$sourceWhere);

        if($ridQueryResult = $this->query($asQuery)) {
            if ($row = $ridQueryResult->fetch_assoc()) {
                $ridQueryResult->close();
                return intval($row["nrOfAvailableSlots"]);
            }else{
                $ridQueryResult->close();
                throw new RandapiException("query did not return a result: $asQuery");
            }
        }else{
            throw new RandapiException("Could not execute query: $asQuery");
        }

    }

    /**
     * @param stdClass $jsonObject
     * @param string $jsonText
     * @throws RandapiException
     */
    public function handleRequest(stdClass $jsonObject, string $jsonText):void{
        if($this->checkToken($jsonObject)){
            if(property_exists($jsonObject,"action")){
                switch($jsonObject->action){
                    case "addRecordsToAllocationTable":
                        $this->handleAddAllocation($jsonObject);
                        echo json_encode("success");
                        break;
                    case "randomizeRecord":
                        $foundAid =$this->handleRandomization($jsonObject);
                        echo json_encode("$foundAid");
                        break;
                    case "availableSlots":
                        $nrOfAvaibaleSlots = $this->handleAvailableSlots($jsonObject);
                        echo json_encode($nrOfAvaibaleSlots);
                        break;
                    default:
                        throw new RandapiException("Invalid Action was specified");
                }
            }else{
                http_response_code(500);
                $exception = new RandapiException("Invalid jsonObject was posted: $jsonText");
                echo json_encode($exception);
            }
        }else{
            error_log("incorrect token");
            throw new RandapiException("You don't have sufficient privileges to access this api.",500);
        }
    }

    /**
     * @param stdClass $jsonObject
     * @return bool
     * @throws RandapiException
     */
    public function checkToken(stdClass $jsonObject):bool {
        if(!$this->getProjectId()){
            throw new RandapiException("projectid was not set");
        }
        if(property_exists($jsonObject,"token")){
            try {
                $token = db_real_escape_string($jsonObject->token);
                // check for project specific token and for super user token
                $tokenQuery = "SELECT 1 as ok
                FROM redcap_user_information i
                JOIN redcap_user_rights u on i.username = u.username
                WHERE u.api_token = '" . db_escape($token) . "'
                AND u.project_id = ".$this->getProjectId()."
                AND i.user_suspended_time is null 
                UNION
                SELECT 1 as ok
                FROM redcap_user_information
                WHERE api_token = '" . db_escape($token) . "'
                AND user_suspended_time IS NULL 
                AND super_user = 1";

                error_log($tokenQuery);

                $tokenQueryResult = $this->query($tokenQuery);
                return !is_null($tokenQueryResult->fetch_assoc());
            }catch(\Exception $e){
                throw new RandapiException("Could not check token status",500,$e);
            }
        }else{
            throw new RandapiException("Token property was not set");
        }
    }

}