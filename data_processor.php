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

    public function __construct()
    {
        $this->dbHost = 'localhost';
        $this->dbName = 'catalyst_users';
        $this->dbUser = 'chen';
        $this->dbPassword = 'lc123';
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

    public function connectToDb()
    {
        // Establish the database connection
        try {
            $this->conn = new PDO("mysql:host=" . $this->dbHost . ";dbname=" . $this->dbName, $this->dbUser, $this->dbPassword);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Database has been connected" . "<br>";
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
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
            echo "Error : The email $email already exists" . "<br>";
            return $validEmail;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Invalid email format
            $validEmail = false;
            echo "Error : $email is invalid email format!" . "<br>";
            return $validEmail;
        }
        return $validEmail;
    }

    public function loadToDb($data)
    {
        if (!$this->conn) {
            $this->connectToDb();
        }
        $count = 0;
        foreach ($data as $index) {
            $userName = $index['name'];
            $userSurname = $index['surname'];
            $userEmail = trim($index['email']);

            if ($this->emailFilter($userEmail) == false) {
                echo "Warning : This line will be skipped." . "<br>";
            } else {
                $stmt = $this->conn->prepare("INSERT INTO users 
                    (name,surname,email)
                    VALUES (:name,:surname,:email)");

                $stmt->bindParam(':name', $userName, PDO::PARAM_STR);
                $stmt->bindParam(':surname', $userSurname, PDO::PARAM_STR);
                $stmt->bindParam(':email', $userEmail, PDO::PARAM_STR);
                $stmt->execute();
                $count++;
            }
        }
        echo $count . " users have been added.";
    }
}
