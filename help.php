<!DOCTYPE>
<html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="<?php echo APP_PATH_WEBROOT_FULL.APP_PATH_CSS.'bootstrap.min.css' ?>">

        <title>Randapi: help</title>
    </head>
    <body>
        <div class="container">
            <h1>RandAPI Usage</h1>
            <p>RandAPI is a rest service that exposes some methods to work with the build in Randomization support of <a href="https://projectredcap.org/">REDCap</a>.</p>
            <p>Basically the randapi accepts a json object that defines an action, a token and some parameters</p>
            <p>The JSON object can be send to an url (e.g. https://localhost/api/?type=module&prefix=Randapi&page=api&NOAUTH)</p>

            <h2>Actions</h2>

            <h3>addRecordsToAllocationTable</h3>
            <p>Adds new records to the allocation table</p>
            <h4>parameters:</h4>
            <ul>
                <li><b>projectId</b>: The project id (integer)</li>
                <li><b>project_status</b>: 0 = development, 1 = production (integer)</li>
                <li><b>allocations</b>: array of new allocation values (see RandomizationAllocation.ts)</li>
            </ul>
            <h4>Example:</h4>
            <p>This example adds </p>
            <pre>
                <code>
                    {
                        "action":"addRecordsToAllocationTable",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":{
                            "projectId":20,
                            "project_status":0,
                            "allocations":[
                                {"target_field":"A","source_fields":["1","2"]},
                                {"target_field":"B","source_fields":["1","2"]},
                                {"target_field":"A","source_fields":["1","2"]},
                                {"target_field":"B","source_fields":["1","2"]}
                            ]
                        }
                    }
                </code>
            </pre>

            <h3>randomizeRecord</h3>
            <p>Randomizes a record</p>
            <h4>parameters:</h4>
            <ul>
                <li><b>recordId</b>: The record that we want to randomize</li>
                <li><b>projectId</b>: The projectId where the record belongs to</li>
                <li><b>fields</b>: An array of RandomizationFields</li>
                <li><b>resultFieldName</b>: The field where the randomization result can be stored.</li>
                <li><b>groupId</b>: (optional) The DAG identifier. default = '' (none)</li>
                <li><b>armName</b>: (optional) The name of the arm. default = 'Arm 1'</li>
                <li><b>eventName</b>: (optional) The name of the event. default = 'Event 1'</li>
            </ul>
            <h4>Example:</h4>
            <p>This example randomizes a record with id 1 (from project with id 20). The randomization is performed using a field called `randgroup` with value 1. The result should be saved in a field called `assignedto`</p>

            <pre>
                <code>
                        "action":"randomizeRecord",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":{
                            "recordId":1,
                            "projectId":20,
                            "fields":[
                                {"key":"randgroup","value":"1"}
                            ],
                            "resultFieldName"=>"assignedto"
                        }
                    }
                </code>
            </pre>
        </div>
    </body>
</html>

