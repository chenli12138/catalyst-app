<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class DataProcessor
{
    private $conn;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPassword;
    private $processedEmails;

    public function __construct($host = 'localhost',  $user = 'chen', $pwd = 'lc123')
    {
        $this->dbHost = $host;
        $this->dbName = 'catalyst_users'; // No databse name is provided in requirement,fixed one used here.
        $this->dbUser = $user;
        $this->dbPassword = $pwd;
        $this->processedEmails = [];
    }

    public function connectToDb()
    {
        // Establish the database connection
        try {
            $this->conn = new PDO("mysql:host=" . $this->dbHost . ";dbname=" . $this->dbName, $this->dbUser, $this->dbPassword);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Database has been connected" . PHP_EOL;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function createTable()
    {
        if (!$this->conn) {
            $this->connectToDb();
        }
        try {
            $query = "CREATE TABLE IF NOT EXISTS `users` (
                                                            `userID` INT NOT NULL AUTO_INCREMENT, 
                                                            `name` VARCHAR(255) NULL, 
                                                            `surname` VARCHAR(255) NOT NULL, 
                                                            `email` VARCHAR(512) NOT NULL, PRIMARY KEY (`userID`), 
                                                            UNIQUE (`email`)
                                                            )";

            $statement = $this->conn->prepare($query);
            $statement->execute();
            echo "Table users has been created.";
            $this->conn = null;
            return true;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            $this->conn = null;
            return false;
        }
    }

    public function csvReader($csvPath)
    {
        $spreadsheet = IOFactory::load($csvPath);
        // Assuming first sheet to read
        $worksheet = $spreadsheet->getActiveSheet();
        // Get the highest row and column which have data
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        // Get the field names from the first row
        $fieldNames = [];
        for ($col = 'A'; $col <= $highestColumn; ++$col) {
            $fieldName = $worksheet->getCell($col . '1')->getValue();
            $fieldNames[] = trim($fieldName);
        }
        // Iterate through the remaining rows to get the data
        $dataArray = [];
        // Start from 2 because first row is field names
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = [];
            $colIndex = 0; // To index into the fieldNames array
            for ($col = 'A'; $col <= $highestColumn; ++$col) {
                $cellValue = $worksheet->getCell($col . $row)->getValue();
                $dataRow[$fieldNames[$colIndex]] = trim($cellValue);
                $colIndex++;
            }
            $dataArray[] = $dataRow;
        }
        //Format the name and surname values
        foreach ($dataArray as &$data) {
            if (isset($data['name'])) {
                $data['name'] = ucfirst(strtolower($data['name']));
            }

            if (isset($data['surname'])) {
                $data['surname'] = ucfirst(strtolower($data['surname']));
            }
        }
        return $dataArray;
    }

    private function emailFilter($email)
    {
        if (!$this->conn) {
            $this->connectToDb();
        }
        $validEmail = true;
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email ");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            // The email already exists
            $validEmail = false;
            fwrite(STDOUT, "Error : The email $email already exists" . PHP_EOL);
            // echo "Error : The email $email already exists" . PHP_EOL;
            return $validEmail;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Invalid email format
            $validEmail = false;
            fwrite(STDOUT, "Error : $email is invalid email format!" . PHP_EOL);
            // echo "Error : $email is invalid email format!" . PHP_EOL;
            return $validEmail;
        } elseif (isset($this->processedEmails[$email])) {
            $validEmail = false;
            fwrite(
                STDOUT,
                "Error : The email $email already in the batch" . PHP_EOL
            );
            // echo "Error : The email $email already in the batch" . PHP_EOL;
            return $validEmail;
        }
        // Mark this email as processed in this batch
        $this->processedEmails[$email] = true;
        return $validEmail;
    }

    public function loadToDb($data, $dryRun = false)
    {
        if (!$this->conn) {
            $this->connectToDb();
        }
        $addCount = 0;
        $skippedCount = 0;
        foreach ($data as $index) {
            $userName = $index['name'];
            $userSurname = $index['surname'];
            $userEmail = trim($index['email']);

            if ($this->emailFilter($userEmail) === false) {
                echo "Warning : This line will be skipped." . PHP_EOL;
                $skippedCount++;
            } else {
                if ($dryRun === false) {
                    $stmt = $this->conn->prepare("INSERT INTO users 
                    (name,surname,email)
                    VALUES (:name,:surname,:email)");

                    $stmt->bindParam(':name', $userName, PDO::PARAM_STR);
                    $stmt->bindParam(':surname', $userSurname, PDO::PARAM_STR);
                    $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
                    $stmt->execute();
                }

                $addCount++;
            }
        }
        $this->conn = null;
        $totalCount = $addCount + $skippedCount;
        if ($dryRun) {

            echo "\033[01;31m -----We are in Dry run mode. No data will be inserted.----- \033[0m" . PHP_EOL;
        }

        echo "Total $totalCount rows in CSV file" . PHP_EOL;
        echo $dryRun ? $addCount . " users will be added to database." . PHP_EOL  : $addCount . " users have been added to database." . PHP_EOL;

        echo $skippedCount . " users cannot be added" . PHP_EOL;
    }
}
