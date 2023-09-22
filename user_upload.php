<?php
require 'data_processor.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$loadData = new DataProcessor();
$csvData = $loadData->csvReader('users.csv');
$loadData->loadToDb($csvData);
