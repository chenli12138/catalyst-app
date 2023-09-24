<?php
require 'data_processor.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);


$fileOption = getopt("u:p:h:", array(
    "file:",
    "create_table",
    "dry_run",
    "help"
));

if (isset($fileOption['help'])) {
    echo "--file [csv file name] - this is the name of the CSV to be parse" . PHP_EOL;
    echo "--create_table - this will cause the MySQL users table to be built (and no further action will be taken" . PHP_EOL;
    echo "--dry_run - this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altere" . PHP_EOL;
    echo "-u - MySQL usernam" . PHP_EOL;
    echo "-p - MySQL passwor" . PHP_EOL;
    echo "-h - MySQL hos" . PHP_EOL;
    echo "--help - which will output the above list of directives with details" . PHP_EOL;
    exit;
}
// All login credentials need to be provided to connect to database
if (isset($fileOption['u']) && isset($fileOption['p']) && isset($fileOption['h'])) {
    $loadData = new DataProcessor($fileOption['h'], $fileOption['u'], $fileOption['p']);
    $loadData->connectToDb();
} else {
    fwrite(STDOUT, "Database credentials not provided" . PHP_EOL);
    exit;
}

if (isset($fileOption['create_table'])) {
    $loadData->createTable();
    exit;
}

if (isset($fileOption['file'])) {
    $csvData = $loadData->csvReader($fileOption['file']);
} else {
    fwrite(STDOUT, "Error : CSV file not provided." . PHP_EOL);
    exit;
}

if (isset($fileOption['dry_run'])) {
    $loadData->loadToDb($csvData, true);
    exit;
} else {
    $loadData->loadToDb($csvData, false);
}
