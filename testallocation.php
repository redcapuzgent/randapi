<?php

// to run the test. Enable the module at project RandomizationTest level

require_once(__DIR__ . "/RandomizationAllocation.php");

use gadeynebram\Randapi\RandomizationAllocation;

try{

    $allocations = array();
    $allocations[0] = new RandomizationAllocation(array("1"),"1");
    $allocations[1] = new RandomizationAllocation(array("1"),"2");
    $allocations[2] = new RandomizationAllocation(array("1"),"1");
    $allocations[3] = new RandomizationAllocation(array("1"),"2");
    $allocations[4] = new RandomizationAllocation(array("1"),"1");
    $allocations[5] = new RandomizationAllocation(array("1"),"2");

    // define project_status = 0 when project is in development.
    $project_status = 0;

    /* @var $module \gadeynebram\Randapi\Randapi*/
    $project_id = $module->getProjectId();

    $ridQuery = "select rid from redcap.redcap_randomization where project_id = $project_id";
    $rid = false;
    if($ridQueryResult = $module->query($ridQuery)){

        if($row = $ridQueryResult->fetch_assoc()){
            $rid = $row["rid"];
        }
        $ridQueryResult->close();
    }else{
        $msg = "Could not execute ridQuery $ridQuery. ".mysqli_error($conn);
        echo $msg;
        error_log($msg);
    }

    if($rid){
        error_log("found rid $rid");
        $module->addRecordsToAllocationTable(intval($rid),$project_status,$allocations);

        // check if allocations where added
        $checkQuery = "
          select count(*) as aantal 
          from redcap.redcap_randomization_allocation rra 
          where rra.rid = $rid and 
            rra.project_status = $project_status and 
            rra.source_field1 = '1'";
        if($checkQueryResult = $module->query($checkQuery)) {
            $aantal = false;
            if ($row = $checkQueryResult->fetch_assoc()) {
                $aantal = $row["aantal"];
            }
            $checkQueryResult->close();

            if($aantal){
                $msg = ($aantal == 16?"Added correctly":"Error");
                echo $msg;
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
        $msg = "Could not get rid";
        echo $msg;
        error_log($msg);
    }

} catch(Exception $e){
    error_log("Could not add allocation rows. ".$e->getMessage().' '.$e->getTraceAsString());
}
