<?php

if (!is_file("../db/ppm.sqlite3")) {
    try {
        $schema = file_get_contents("../model/schema.sql");
    } catch(Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
        exit("Failed to find sql schema file. Database cannot be created.");
    }
} else {
    $schema = NULL;
}

try {
    $db = "../db/ppm.sqlite3";
    $pdo = new PDO("sqlite:$db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($schema) {
        $pdo->exec($schema);
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    return null;
}
