<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."vendor/autoload.php");
require_once(__DIR__.DIRECTORY_SEPARATOR . "model/RandomizationField.php");
require_once(__DIR__. "/test_utils/count_allocations.php");

use IU\PHPCap\RedCapProject;
use redcapuzgent\Randapi\RandomizationField;


try {

    $token = $_GET["token"];
    /* @var $module \redcapuzgent\Randapi\Randapi*/
    $apiUrl = APP_PATH_WEBROOT_FULL."api/";

    error_log("retrieved token $token and url $apiUrl and projectId ".$module->getProjectId());

    $project = new RedCapProject($apiUrl,$token);
    $existingRecords = REDCap::getData(array(
        'return_format'=>'array',
        'fields'=>"record_id"
    ));

    //$existingRecords = $project->exportRecords('php','flat',null,array("record_id"));

    error_log("retrieved existing records: " . print_r($existingRecords, true));

    if (sizeof($existingRecords) > 0) {
        $recordsToDelete = array();
        foreach ($existingRecords as $recordId => $record) {
            error_log("removing record: " . $recordId);
            $recordsToDelete[sizeof($recordsToDelete)] = $recordId;
        }
        error_log("deleting records with ids: " .print_r($recordsToDelete,true));
        $deleteResult = $project->deleteRecords($recordsToDelete);
        if ($deleteResult != 3) {
            error_log("response after deleting: " . print_r($deleteResult, true));
            error_log("Could not import records!!!");
        } else {
            error_log("deleted successfully");
        }
    }

    $nrOfAvailableSlots = countAllocations($token, $module->getProjectId(),array("1"));
    if($nrOfAvailableSlots != 10){
        echo "The number of available slots before randomization was not 10 but was $nrOfAvailableSlots";
    }

// import test dataset

    $testSet = array();
    $testSet[0] = new stdClass();
    $testSet[0]->record_id = 1;
    $testSet[0]->randgroup = 1; //A
    $testSet[0]->general_complete = 2; //Complete
    $testSet[1] = new stdClass();
    $testSet[1]->record_id = 2;
    $testSet[1]->randgroup = 1; //A
    $testSet[1]->general_complete = 2; //Complete
    $testSet[2] = new stdClass();
    $testSet[2]->record_id = 3;
    $testSet[2]->randgroup = 2; //B
    $testSet[2]->general_complete = 2; //Complete

    $importResponse = $project->importRecords($testSet);

    if ($importResponse != 3) {
        error_log("response after importing: " . print_r($importResponse, true));
        error_log("Could not import records!!!");
    } else {
        error_log("imported successfully");
    }

// randomize



    $assignedAids = array();
    foreach ($testSet as $test) {
        $fields = array(new RandomizationField("randgroup", $test->randgroup));
        try {

            $url = APP_PATH_WEBROOT_FULL."api/?type=module&prefix=Randapi&page=api&NOAUTH&pid=".$module->getProjectId();
            $postfields = [
                "action"=>"randomizeRecord",
                "token"=>$token,
                "parameters"=> [
                    "recordId" => $test->record_id,
                    "fields" => $fields,
                    "resultFieldName"=>"assignedto"
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to TRUE for production use
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Set to TRUE for production use
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

            $output = curl_exec($ch);
            if(!$output){
                echo 'Curl error: ' . curl_error($ch)."\n";
            }else{
            }
            curl_close($ch);
            $assignedAid = json_decode($output);

            error_log("assigned for records $test->record_id aid: $output (decoded $assignedAid)");
            $assignedAids[$test->record_id - 1] = $assignedAid;
        } catch (Exception $e) {
            error_log("error while randomizing: " . $e->getMessage() . " " . $e->getTraceAsString());
        }

    }

    $nrOfAvailableSlots = countAllocations($token, $module->getProjectId(),array("1"));
    // 2 were assigned to randgroup 1. We expect 8 more to be available.
    if($nrOfAvailableSlots != 8){
        echo "The number of available slots after randomization was not 8 but was $nrOfAvailableSlots";
    }

    $testSet[0]->expected = '1'; //X
    $testSet[1]->expected = '2'; //Y
    $testSet[2]->expected = '1'; //X

    $addaptedRecords = $project->exportRecords('php','flat',null,array("record_id","assignedto"));

    $i = 0;
    foreach($addaptedRecords as $record){
        if($record["assignedto"] == $testSet[$i]->expected){
            $msg = "record $i randomized ok";
            error_log($msg);
            echo $msg."<br />";
        }else{
            $msg = "record $i expected was '".$testSet[$i]->expected."' ".gettype($testSet[$i]->expected)." but found '".$record["assignedto"]."' ".gettype($record["assignedto"]);
            error_log($msg);
            echo $msg."<br />";
        }
        $i++;
    }

}catch(RandException $e){
    error_log("error while executing testrandomization: " . $e->getMessage() . " " . $e->getTraceAsString());
    echo $e->getMessage();
}catch(Exception $e){
    error_log("error while executing testrandomization: " . $e->getMessage() . " " . $e->getTraceAsString());
    echo $e->getMessage();
}

