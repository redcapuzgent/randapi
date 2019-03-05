<?php
function countAllocations($token, $projectid,$source_fields){
    $url = APP_PATH_WEBROOT_FULL."api/?type=module&prefix=Randapi&page=api&NOAUTH&pid=".$projectid;
    $fields = [
        "action"=>"availableSlots",
        "token"=>$token,
        "parameters"=> [
            "source_fields" => $source_fields
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
    echo "The availableSlots action returned: $output<br />";
    return $jsonDecoded;
}