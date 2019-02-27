# API to allow usage of the RedCap Randomization from plugins

## Randomization Test

A test for this API was written in testrandomization.php.

The test can be executed by calling url https://localhost/plugins/DAL/Randomization/testrandomization.php.

It requires:

* The RandomizationTest project to be loaded. cfr. resources/RandomizationTest_dist.csv for the project dictionary or RandomizationTest.xml for the full project metadata xml. 
* A user with an access token who is assigned to the Project Admin role. This user must be able to perform randomization and to add records.
* The Allocation table to be loaded. Cfr. resources/RandomizationAllocation.csv

There are 3 groups (A,B and C). They are saved in `randgroup`.
Randomization must occur within these groups for 2 possible outcomes (X and Y). The result is saved in `assignedto`.

The test adds 3 records.

1. randgroup = 1 (A), expected = 1 (X)
2. randgroup = 1 (A), expected = 2 (Y)
3. randgroup = 2 (B), expected = 1 (X)

## Allocation Test

The allocation test adds 6 new allocations to the allocation table. It assumes the original allocation table is loaded prior to executing the test.

You can execute it by altering the url below to your configuration.

https://localhost/api/?type=module&prefix=Randapi&page=api&NOAUTH
