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
     * Initializes global variables, used by Randomization::randomizeRecord
     * @param string $recordId The record that we want to randomize
     * @param int $projectId The projectId where the record belongs to
     * @throws Exception
     */
    private function initRandomizeRecord(string $recordId,int $projectId){
        // set globals required for  Randomization::getRandomizationFields;
        global $redcap_version;
        $classesPath = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."redcap_v$redcap_version".DIRECTORY_SEPARATOR."Classes".DIRECTORY_SEPARATOR;
        require_once($classesPath."Randomization.php");

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
    }

    /**
     * @param string $recordId The record that we want to randomize
     * @param int $projectId The projectId where the record belongs to
     * @param RandomizationField[] $fields An array of RandomizationFields
     * @param string $resultFieldName The field where the randomization result can be stored.
     * @param string $group_id (optional) The DAG identifier. default = '' (none)
     * @param string $arm_name (optional) The name of the arm. default = 'Arm 1'
     * @param string $event_name (optional) The name of the event. default = 'Event 1'
     * @return string returns the field value result of the randomization
     * @throws Exception
     */
    function randomizeRecord(string $recordId,int $projectId,array $fields=array(),string $resultFieldName,string $group_id='',string $arm_name='Arm 1', string $event_name='Event 1'): string{
        $this->initRandomizeRecord($recordId, $projectId);

        $tfields = array();
        foreach($fields as $field) {
            $tfields[$field->getKey()] = $field->getValue();
        }
        error_log("randomizing using fields ".print_r($tfields,true));
        // calls Randomization::getRandomizationFields
        $aid =  Randomization::randomizeRecord($recordId,$tfields,$group_id);
        if($aid){
            // retrieve randomization value (target_field)

            // SQL Inject check
            // ----------------

            // $aid is a method result

            $query = "select target_field from redcap_randomization_allocation rra where rra.aid = $aid";

            $randomizationQueryResult = $this->query($query);
            $randomizationResult = null;
            if($row = $randomizationQueryResult->fetch_assoc()){
                $randomizationResult = $row["target_field"];
            }
            $randomizationQueryResult->close();
            if(!is_null($randomizationResult)){
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
                insert into redcap_data(project_id, event_id, record, field_name, `value`)
                select $projectId as project_id, md.event_id, '$recordId' as record, '$resultFieldName' as field_name, '$randomizationResult' as `value`
                from redcap_events_arms a
                join redcap_events_metadata md on 
                    a.arm_id = md.arm_id and
                    md.descrip = '$event_name'
                where a.project_id = $projectId and
                    a.arm_name='$arm_name';";
                error_log("Executing query $query");
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
     * @param $criteriaFields
     * @param array $fields
     * @param string $group_id
     * @param $currentTargetField
     * @return int 0 in case of no value.
     */
    function getFreeAllocationRecordForTarget($criteriaFields,$fields=array(),$group_id='',$currentTargetField){
        global $status;

        $tfields = array();
        foreach($fields as $field) {
            $tfields[$field->getKey()] = $field->getValue();
        }

        // Create sql subquery for DAG
        $sqlsub = "and a.group_id" . (is_numeric($group_id) ? " = $group_id " : " is null ");
        // Create sql subquery for strata critera
        foreach ($criteriaFields as $col=>$field) {
            $sqlsub .= "and a.$col = '".db_escape($tfields[$field])."' ";
        }
        // extra criteria: target_field = $currentTargetField
        $sqlsub .= "and a.target_field = '$currentTargetField' ";
        // Query to get an aid key for these field value combinations
        $sql = "select a.aid from redcap_randomization_allocation a, redcap_randomization r
				where r.project_id = " . PROJECT_ID . " and r.rid = a.rid and a.project_status = $status
				and a.is_used_by is null $sqlsub order by a.aid limit 1";
        error_log("executing query $sql");
        $q = db_query($sql);
        if (db_num_rows($q) < 1) {
            // Return as 0 to give error message about being already allocated
            return 0;
        } else {
            // Get the NEXT aid matching our criteria
            $aid = db_result($q, 0);
            return $aid;
        }
    }

    /**
     * Due to wrong registration, it might be necessary to change the source fields that are used to randomize a record.
     * This can be done using the following steps
     * 1) Check if an allocation record is available for the given recorid
     * 2) Collect the current target_field value.
     * 3) Look for a new allocation record (aid) for the new source_field values but for the same target_field.
     * 4) Select the first free allocation. If none available, add new ones as defined in $newAllocations and select first (aid)
     * 5) Update is_used_by field for the current allocation to null, update the new allocation to the new found aid.
     * 6) Update the source field values in the record
     *
     * @param string $recordId
     * @param int $projectId
     * @param RandomizationField[] $fields
     * @param string $group_id
     * @param RandomizationAllocation[] $allocations These allocations will be added if no allocations are available for the given combination of source fields and the target field.
     * @param string $arm_name (optional) The name of the arm. default = 'Arm 1'
     * @param string $event_name (optional) The name of the event. default = 'Event 1'
     * @return int
     * @throws Exception
     */
    function changeSources(string $recordId,int $projectId,$fields=array(),array $allocations,$group_id='',string $arm_name='Arm 1', string $event_name='Event 1'){

        error_log("init randomize record");
        $this->initRandomizeRecord($recordId, $projectId);

        global $status;

        $recordId = db_real_escape_string($recordId);

        // What is the current target_field value?
        $currentTargetFieldQuery = $this->query("SELECT ra.target_field, ra.aid
                    FROM redcap_randomization r
                    join redcap_randomization_allocation ra on 
                        ra.rid = r.rid and
                        ra.is_used_by = '$recordId' and 
                        ra.project_status = $status
                    where r.project_id = $projectId");
        if($row = $currentTargetFieldQuery->fetch_assoc()){
            $currentTargetField = $row["target_field"];
            $currentAid = $row["aid"];
            error_log("Received current target field $currentTargetField and current aid $currentAid");

            // copy of Randomization::randomizeRecord
            // Ensure that fields have all correct criteria fields. If not, return false to throw AJAX error msg.
            $criteriaFieldsOk = true;
            $criteriaFields = Randomization::getRandomizationFields(false,true);
            if (count($fields) != count($criteriaFields)) $criteriaFieldsOk = false;
            foreach (array_keys($fields) as $field) {
                if (!in_array($field, $criteriaFields)) $criteriaFieldsOk = false;
            }

            if(!$criteriaFieldsOk){
                throw new Exception("The given criteria fields are not valid");
            }
            error_log("criteria fields are oké.");
            $newAid = $this->getFreeAllocationRecordForTarget($criteriaFields, $fields, $group_id, $currentTargetField);
            if($newAid == 0) {
                error_log("No allocations are present");
                if (is_array($allocations) && sizeof($allocations) > 0) {
                    error_log("Adding new allocations");
                    $this->addRecordsToAllocationTable($projectId, $status, $allocations);
                    $newAid = $this->getFreeAllocationRecordForTarget($criteriaFields, $fields, $group_id, $currentTargetField);
                } else {
                    throw new RandapiException('No free allocation was available and no allocations were passed through the $allocations argument');
                }
            }
            if($newAid != 0){
                error_log("Found a new aid $newAid");
                // No need to change the target_field value in redcap_data. This remains the same.
                if(!$this->query("update redcap_randomization_allocation set is_used_by = null where aid = '$currentAid' and is_used_by = '$recordId'")){
                    throw new RandapiException("Could not unsed is_used_by for aid $currentAid");
                }
                error_log("updated is_used_by from old aid $currentAid to null");
                if(!$this->query("update redcap_randomization_allocation set is_used_by = '$recordId' where aid = $newAid;")){
                    throw new RandapiException("Could not set is_used_by for aid $newAid");
                }
                error_log("updated is_used_by from new aid $newAid to $recordId");
                // update the source fields in the record
                $i = 0;
                foreach($fields as $sourcefield){
                    $updateQuery = "
                        update redcap_data rd
                        join redcap_events_metadata md on 
                            md.event_id = rd.event_id and
                            md.descrip = '$event_name'
                        join redcap_events_arms a on a.arm_id = md.arm_id and
                            a.arm_name = '$arm_name'
                        join redcap_randomization_allocation cura on 
                            cura.aid = $currentAid and 
                            cura.source_field".($i+1)." = rd.value
                        join redcap_randomization_allocation newa on newa.aid = $newAid
                        set rd.value = newa.source_field".($i+1)."
                        where rd.project_id = $projectId and
                            rd.record = '$recordId' and
                            rd.field_name = '".$sourcefield->getKey()."'
                    ";
                    error_log("Executing query $updateQuery");
                    if(!$this->query($updateQuery)){
                        throw new RandapiException("Could not update field $i '".$sourcefield->getKey()."' to value '".$sourcefield->getValue()."' for project $projectId, record $recordId, event $event_name and arm $arm_name from aid $currentAid to aid $newAid");
                    }else{
                        error_log("updated field $i '".$sourcefield->getKey()."' to value '".$sourcefield->getValue()."' for project $projectId, record $recordId, event $event_name and arm $arm_name from aid $currentAid to aid $newAid");
                    }
                    $i++;
                }
                return $newAid;
            }else{
                throw new RandapiException("Could not find an empty allocation record for the given target and source fields");
            }

        }else{
            throw new RandapiException("Record $recordId has not yet been randomized");
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

        $ridQuery = "select rid from redcap_randomization where project_id = $projectId";
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
     * @throws RandapiException
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
                    case "findAID":
                        $aid = $this->handleFindAID($jsonObject);
                        echo json_encode($aid);
                        break;
                    case "undoRandomization":
                        $this->handleUndoRandomization($jsonObject);
                        echo json_encode("success");
                        break;
                    case "changeSources":
                        $newAid = $this->handleChangeSources($jsonObject);
                        echo json_encode($newAid);
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
    private function checkToken(stdClass $jsonObject):bool {
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

    /**
     * @param stdClass $jsonObject
     * @return int
     * @throws RandapiException
     */
    private function handleFindAID(stdClass $jsonObject): int {
        if(!property_exists($jsonObject,"parameters")){
            throw new RandapiException("parameters property not found.");
        }

        try {

            $recordid = db_real_escape_string($jsonObject->parameters);

            $aidQuery = "
                select rra.aid
                from redcap_projects rp 
                join redcap_randomization rr on rr.project_id = rp.project_id
                join redcap_randomization_allocation rra on 
                    rr.rid = rra.rid and
                    rra.project_status = rp.status and
                    rra.is_used_by = '$recordid'
                where rp.project_id = ".$this->getProjectId();

            error_log("executing aidQuery $aidQuery");

            $aidQueryResult = $this->query($aidQuery);

            $res = $aidQueryResult->fetch_object();
            if (!is_null($res)) {
                return $res->aid;
            } else {
                throw new RandapiException("recordid $$recordid is not found",400);
            }
        }catch(\Exception $e){
            throw new RandapiException("Could not execute handleFindAID",500,$e);
        }
    }

    /**
     * @param stdClass $jsonObject
     * @throws RandapiException
     */
    private function handleUndoRandomization(stdClass $jsonObject){
        if(!property_exists($jsonObject,"parameters")){
            throw new RandapiException("parameters property not found.");
        }
        $recordid = $jsonObject->parameters;
        $aid = $this->handleFindAID($jsonObject);
        if($aid){
            $ok = $this->query("
                delete d
                from redcap_randomization_allocation ra
                join redcap_randomization r on r.rid = ra.rid  
                join redcap_data d on 
                    d.project_id = r.project_id and 
                    d.field_name = r.target_field and
                    (r.target_event is null or d.event_id = r.target_event) and
                    d.record = ra.is_used_by
                where ra.aid = $aid
            ");
            if($ok){
                $ok = $this->query("
                    update redcap_randomization_allocation 
                    set is_used_by = null 
                    where aid = $aid
                ");
                if(!$ok){
                    throw new RandapiException("Could not unset allocation record for record $recordid and aid $aid");
                }
            }else{
                throw new RandapiException("Could not delete data record for record $recordid and aid $aid");
            }
        }else{
            throw new RandapiException("Could not find aid for record $recordid");
        }

    }


    /**
     * @param stdClass $jsonObject
     * @return int
     * @throws RandapiException
     */
    private function handleChangeSources(stdClass $jsonObject){

        error_log("Received parameters in changeSources: ".print_r($jsonObject,true));

        if(!property_exists($jsonObject,"parameters")){
            error_log("parameters property not found.");
            throw new RandapiException("parameters property not found.");
        }
        if(!property_exists($jsonObject->parameters, "recordId")){
            error_log("parameters->recordId property not found.");
            throw new RandapiException("parameters->recordId property not found.");
        }
        if(!property_exists($jsonObject->parameters, "fields")){
            error_log("parameters->fields property not found.");
            throw new RandapiException("parameters->fields property not found.");
        }
        if(!is_array($jsonObject->parameters->fields)){
            error_log("parameters->fields is not an array.");
            throw new RandapiException("parameters->fields is not an array.");
        }
        if(!property_exists($jsonObject->parameters, "allocations")){
            error_log("parameters->allocations property not found.");
            throw new RandapiException("parameters->allocations property not found.");
        }
        if(!is_array($jsonObject->parameters->allocations)){
            error_log("parameters->allocations is not an array.");
            throw new RandapiException("parameters->allocations is not an array.");
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

        $allocations = array();
        foreach($jsonObject->parameters->allocations as $allocation){
            array_push($allocations,RandomizationAllocation::fromstdClass($allocation));
        }

        error_log("executing changeSources");
        return $this->changeSources($jsonObject->parameters->recordId,
            $this->getProjectId(),
            $fields,
            $allocations,
            $groupId,
            $armName,
            $eventName);
    }

}