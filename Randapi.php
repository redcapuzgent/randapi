<?php
namespace redcapuzgent\Randapi;

use ExternalModules\AbstractExternalModule;
use Exception;
use Randomization;

class Randapi extends AbstractExternalModule
{

    public function __construct(){
        parent::__construct();
    }

    /**
     * @param string $recordId The record that we want to randomize
     * @param string $projectId The projectId where the record belongs to
     * @param RandomizationField[] $fields An array of RandomizationFields
     * @param string$resultFieldName The field where the randomization result can be stored.
     * @param string $group_id (optional) The DAG identifier. default = '' (none)
     * @param string $arm_name (optional) The name of the arm. default = 'Arm 1'
     * @param string $event_name (optional) The name of the event. default = 'Event 1'
     * @return string returns the field value result of the randomization
     * @throws Exception
     */
    function randomizeRecord($recordId,$projectId,$fields=array(),$resultFieldName,$group_id='',$arm_name='Arm 1', $event_name='Event 1'){
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
                $query = "
                insert into redcap.redcap_data(project_id, event_id, record, field_name, `value`)
                select $projectId as project_id, md.event_id, '$recordId' as record, '$resultFieldName' as field_name, '$randomizationResult' as `value`
                from redcap.redcap_events_arms a
                join redcap.redcap_events_metadata md on 
                    a.arm_id = md.arm_id and
                    md.descrip = 'Event 1'
                where a.project_id = 20 and
                    a.arm_name='Arm 1';";
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
     * @param int $rid
     * @param int $project_status (0 = development, 1 = production)
     * @param RandomizationAllocation[] $allocations
     * @throws Exception
     */
    public function addRecordsToAllocationTable(int $rid,int $project_status,array $allocations){
        foreach($allocations as $allocation){
            $sourceFieldNames = array();
            for($i = 0; $i < sizeof($allocation->getSourceFields()); $i++){
                $sourceFieldNames[$i] = "source_field".($i+1);
            }

            $query = "
                  insert into redcap_randomization_allocation(rid,project_status,target_field,".implode(',',$sourceFieldNames).")
                  values($rid,$project_status,'".$allocation->getTargetField()."','".implode("','",$allocation->getSourceFields())."');";

            error_log("Executing query: $query");
            if(!$this->query($query)){
                throw new Exception("Could not add allocation values to table for query: $query. Exception: ".mysqli_error($this->conn));
            }
        }
    }
}