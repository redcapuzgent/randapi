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
            <p>RandAPI is a rest service that exposes some methods to work with the build in randomization support of <a href="https://projectredcap.org/">REDCap</a>.</p>
            <p>Basically the randapi accepts a json object that defines an action and some parameters</p>

            <h2>Methods</h2>

            <h3>addRecordsToAllocationTable</h3>
            <p>Adds new records to the allocation table</p>
            <h4>parameters:</h4>
            <ul>
                <li><b>rid</b>: The record to randomize (integer)</li>
                <li><b>project_status</b>: 0 = development, 1 = production (integer)</li>
                <li><b>allocations</b>: array of new allocation values (see RandomizationAllocation.ts)</li>
            </ul>
            <h4>Example:</h4>
            <p>Send the following json object to <code>https://localhost/api/?type=module&prefix=Randapi&page=api&NOAUTH</code>.</p>
            <pre>
                <code>
                    {
                        "action":"addRecordsToAllocationTable",
                        "parameters":{
                            "rid":1,
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
        </div>
    </body>
</html>

