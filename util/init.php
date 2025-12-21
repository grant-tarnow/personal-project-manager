<?php

echo "NO DB FILE FOUND";

try {
    $schema = file_get_contents("../model/schema.sql");
} catch {
    exit("Failed to find sql schema file. Database cannot be created.");
}
