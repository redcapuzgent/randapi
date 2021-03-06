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
            <p>The value should be a recordid</p>
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

            <h2>undoRandomization</h2>
            <p>Undo the randomization for a certain record in the redcap_randomization_allocation table.</p>
            <h3>parameters:</h3>
            <p>The value should be a recordid</p>
            <h3>Example:</h3>
            <p>This example undoes the allocation of record 1</p>

            <pre>
                <code>
                    {
                        "action":"undoRandomization",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":"1"
                    }
                </code>
            </pre>

            <h2>changeSources</h2>
            <p>Due to wrong registration, it might be necessary to change the source fields that are used to randomize a record.</p>
            <p>If possible, another allocation record for the new source combination will be used that maintains the current assigned group.</p>
            <p>If no such records exist, there is a possibility to provide additional allocation records</p>

            <h3>parameters:</h3>
            <ul>
                <li><b>recordId</b>: The record that we want to change source fields for</li>
                <li><b>fields</b>: An array of RandomizationFields. (see <a href="https://github.com/redcapuzgent/randapi/blob/master/typescript/RandomizationField.ts" >RandomizationFields.ts</a>)</li>
                <li><b>allocations</b>: A list of new allocation records, in case there is no record available anymore.
                <li><b>groupId</b>: (optional) The DAG identifier. default = '' (none)</li>
                <li><b>armName</b>: (optional) The name of the arm. default = 'Arm 1'</li>
                <li><b>eventName</b>: (optional) The name of the event. default = 'Event 1'</li>
            </ul>
            <h3>Example:</h3>
            <p>This examples changes the source fields for record 1 to randgroup 2 (it was 1 previously. In case there is no allocation record available anymore, we allow the algorithm to add these 4 new allocations.</p>

            <pre>
                <code>
                    {
                        "action":"changeSources",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":{
                            "recordId":1,
                            "fields":[
                                {"key":"randgroup","value":"2"}
                            ],
                            "allocations":[
                                {"source_fields":["2"],"target_field":"1"},
                                {"source_fields":["2"],"target_field":"2"},
                                {"source_fields":["2"],"target_field":"1"},
                                {"source_fields":["2"],"target_field":"2"},
                            ]
                        }
                    }
                </code>
            </pre>

            <h2>changeTarget</h2>
            <p>In limited cases it might be necessary to change the outcome of the randomization. E.g. in an automated process a record was assigned to a certain target group. Due to some manual changes, the record is unrandomized. Correcting the error and randomizing the record again, results in a different target value.</p>
            <p>If possible, another allocation record for the preferred target will be used that maintains the current assigned sources.</p>
            <p>If no such records exist, there is a possibility to provide additional allocation records</p>

            <h3>parameters:</h3>
            <ul>
                <li><b>recordId</b>: The record that we want to change source fields for</li>
                <li><b>target</b>: The new target value</li>
                <li><b>allocations</b>: A list of new allocation records, in case there is no record available anymore.
                <li><b>groupId</b>: (optional) The DAG identifier. default = '' (none)</li>
                <li><b>armName</b>: (optional) The name of the arm. default = 'Arm 1'</li>
                <li><b>eventName</b>: (optional) The name of the event. default = 'Event 1'</li>
            </ul>
            <h3>Example:</h3>
            <p>This examples changes the target for record 1 to assignedto A. In case there is no allocation record available anymore, we allow the algorithm to add these 4 new allocations.</p>

            <pre>
                <code>
                    {
                        "action":"changeTarget",
                        "token":"F33F6876ADC5EC63CE79EBFF88FF0092",
                        "parameters":{
                            "recordId":1,
                            "target":'A',
                            "allocations":[
                                {"source_fields":["2"],"target_field":"1"},
                                {"source_fields":["2"],"target_field":"2"},
                                {"source_fields":["2"],"target_field":"1"},
                                {"source_fields":["2"],"target_field":"2"},
                            ]
                        }
                    }
                </code>
            </pre>

            <h2>readConfiguration</h2>
            <p>This method must be executed in a GET request and extracts the randomization configuration settings from REDCap for the given project</p>
            <p>The API exports this configuration in a CSV format.</p>
            <p>Example URL: https://localhost/api/?type=module&prefix=randapi&page=api&pid=19&NOAUTH&token=F33F6876ADC5EC63CE79EBFF88FF0092&action=readConfiguration</p>

            <h2>readAllocations</h2>
            <p>This method must be executed in a GET request and extracts the allocation table for the given project (and status) from REDCap.</p>
            <p>The status parameter is required. The value 0 van be passed for projects in development status and 1 for projects with a production status.</p>
            <p>The API exports this allocations in a CSV format.</p>
            <p>Example URL: https://localhost/api/?type=module&prefix=randapi&page=api&pid=19&NOAUTH&token=F33F6876ADC5EC63CE79EBFF88FF0092&action=readAllocations&status=0</p>

        </div>
    </body>
</html>

