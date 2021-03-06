<?php

// to run the test. Enable the module at project RandomizationTest level

require_once __DIR__.DIRECTORY_SEPARATOR."vendor/autoload.php";

require_once(__DIR__. "/test_utils/count_allocations.php");

use redcapuzgent\Randapi\model\RandomizationAllocation;

try{

    $token = $_GET["token"];

    // define project_status = 0 when project is in development.
    $project_status = 0;

    $expectedNrOfInitialAllocations = 10;
    $nrOfInitialAllocations = countAllocations($token,$module->getProjectId(),array("1"));

    if($nrOfInitialAllocations==$expectedNrOfInitialAllocations){

        $allocations = array();
        $allocations[0] = new RandomizationAllocation(array("1"),"1");
        $allocations[1] = new RandomizationAllocation(array("1"),"2");
        $allocations[2] = new RandomizationAllocation(array("1"),"1");
        $allocations[3] = new RandomizationAllocation(array("1"),"2");
        $allocations[4] = new RandomizationAllocation(array("1"),"1");
        $allocations[5] = new RandomizationAllocation(array("1"),"2");



        /* @var $module \redcapuzgent\Randapi\Randapi*/

        // call api method
        $url = APP_PATH_WEBROOT_FULL."api/?type=module&prefix=Randapi&page=api&NOAUTH&pid=".$module->getProjectId();
        $fields = [
            "action"=>"addRecordsToAllocationTable",
            "token"=>$token,
            "parameters"=> [
                "project_status" => $project_status,
                "allocations" => $allocations
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
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
        $jsonDecoded = json_decode($output);

        echo "The service returned: $output\n";

        if($jsonDecoded == "success"){

            // check if allocations where added
            $checkQuery = "
              select count(*) as aantal 
              from redcap.redcap_randomization_allocation rra 
              join redcap.redcap_randomization rr on 
                rr.rid = rra.rid and 
                rr.project_id = ".$module->getProjectId()."
              where rra.project_status = $project_status and 
                rra.source_field1 = '1'";
            if($checkQueryResult = $module->query($checkQuery)) {
                $aantal = false;
                if ($row = $checkQueryResult->fetch_assoc()) {
                    $aantal = $row["aantal"];
                }
                $checkQueryResult->close();

                if($aantal){
                    $msg = ($aantal == 16?"Added correctly":"Error, aantal: $aantal");

                    $newNrOfAllocations = countAllocations($token,$module->getProjectId(),array("1"));
                    if($newNrOfAllocations == 16){
                        $msg .="<br />Correct new number of allocations.";
                    }else{
                        $msg .="<br />Incorrect new number of allocations $newNrOfAllocations.";
                    }
                    echo "Test result: $msg";
                    error_log($msg);
                }else{
                    $msg = "Could not get new allocation count";
                    echo $msg;
                    error_log($msg);
                }
            }else{
                $msg = "Could not execute checkQuery $checkQuery. ".mysqli_error($conn);
                echo $msg;
                error_log($msg);
            }

        }else{
            $msg = "api call failed: ";
            echo $msg;
            error_log($msg);
        }

    }else{
        $msg = "Initial number of allocations was not $expectedNrOfInitialAllocations but $nrOfInitialAllocations. Please reset the allocation table prior to running this test.";
        echo $msg;
        error_log($msg);
    }

} catch(Exception $e){
    error_log("Could not add allocation rows. ".$e->getMessage().' '.$e->getTraceAsString());
}
