<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."vendor/autoload.php");
require_once(__DIR__.DIRECTORY_SEPARATOR . "RandomizationField.php");

use IU\PHPCap\RedCapProject;
use gadeynebram\Randapi\RandomizationField;


try {

    $token = $_GET["token"];
    /* @var $module \gadeynebram\Randapi\Randapi*/
    $apiUrl = "https://".$_SERVER['HTTP_HOST']."/api/"; //$module->getUrl("",true, true);

    error_log("retrieved token $token and url $apiUrl");

    $project = new RedCapProject($apiUrl,$token);
    $existingRecords = $project->exportRecords('php','flat',null,array("record_id"));

    error_log("retrieved existing records: " . print_r($existingRecords, true));

    if (sizeof($existingRecords) > 0) {
        $recordsToDelete = array();
        foreach ($existingRecords as $record) {
            error_log("removing record: " . $record["record_id"]);
            $recordsToDelete[sizeof($recordsToDelete)] = $record["record_id"];
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

    if ($importResponse->count != 3) {
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
            $assignedAid = $module->randomizeRecord($test->record_id, $project_id, $fields,"assignedto");
            error_log("assigned for records $test->record_id aid: $assignedAid");
            $assignedAids[$test->record_id - 1] = $assignedAid;
        } catch (Exception $e) {
            error_log("error while randomizing: " . $e->getMessage() . " " . $e->getTraceAsString());
        }

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

} catch(Exception $e){
    error_log("error while executing testrandomization: " . $e->getMessage() . " " . $e->getTraceAsString());
}

