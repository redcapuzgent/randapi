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
            <p>The JSON object can be send to an url (e.g. https://localhost/api/?type=module&prefix=Randapi&page=api&NOAUTH&pid=xxx). Most methods require you to set the pid get parameter and validate that you may perform operations within the project using the token. NOAUTH must be specified because this is an api and it is not possible to first login to REDCap.</p>

            <h1>Actions</h1>

            <h2>addRecordsToAllocationTable</h2>
            <p>Adds new records to the allocation table</p>
            <h3>parameters:</h3>
            <ul>
                <li><b>project_status</b>: 0 = development, 1 = production (integer)</li>
                <li><b>allocations</b>: Array of new allocation values. (see <a href="https://github.com/redcapuzgent/randapi/blob/master/typescript/RandomizationAllocation.ts" >RandomizationAllocation.ts</a>)</li>
            </ul>
            <h3>Example:</h3>
            <p>This example adds </p>
            <pre>
                <code>
                    {
                        "action":"addRecordsToAllocationTable",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":{
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

            <h2>randomizeRecord</h2>
            <p>Randomizes a record</p>
            <h3>parameters:</h3>
            <ul>
                <li><b>recordId</b>: The record that we want to randomize</li>
                <li><b>fields</b>: An array of RandomizationFields. (see <a href="https://github.com/redcapuzgent/randapi/blob/master/typescript/RandomizationField.ts" >RandomizationFields.ts</a>)</li>
                <li><b>resultFieldName</b>: The field where the randomization result can be stored.</li>
                <li><b>groupId</b>: (optional) The DAG identifier. default = '' (none)</li>
                <li><b>armName</b>: (optional) The name of the arm. default = 'Arm 1'</li>
                <li><b>eventName</b>: (optional) The name of the event. default = 'Event 1'</li>
            </ul>
            <h3>Example:</h3>
            <p>This example randomizes a record with id 1 (from project with id 20). The randomization is performed using a field called `randgroup` with value 1. The result should be saved in a field called `assignedto`</p>

            <pre>
                <code>
                    {
                        "action":"randomizeRecord",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":{
                            "recordId":1,
                            "fields":[
                                {"key":"randgroup","value":"1"}
                            ],
                            "resultFieldName"=>"assignedto"
                        }
                    }
                </code>
            </pre>


            <h2>availableSlots</h2>
            <p>Check the number of available slots in the allocation table for a given target_field and a set of source_fields.</p>
            <h3>parameters:</h3>
            An instance of AvailableSlotsParameters.ts  (see <a href="https://github.com/redcapuzgent/randapi/blob/master/typescript/AvailableSlotsParameters.ts" >AvailableSlotsParameters.ts</a>)
            <h3>Example:</h3>
            <p>This example retrieves the number of records that are available in the allocation table for a target result 1 and a combination of source field values </p>

            <pre>
                <code>
                    {
                        "action":"randomizeRecord",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":{
                            "source_fields":["1","2"]
                        }
                    }
                </code>
            </pre>

            <h2>findAID</h2>
            <p>Find the redcap_randomization_allocation aid value where a certain record is used.</p>
            <h3>parameters:</h3>
            The value should be a recordid
            <h3>Example:</h3>
            <p>This example retrieves the aid for a record with record id 1.</p>

            <pre>
                <code>
                    {
                        "action":"findAID",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":"1"
                    }
                </code>
            </pre>
        </div>
    </body>
</html>

