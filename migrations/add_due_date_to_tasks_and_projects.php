<?php

// Run this from the migrations directory
// Make a backup of ppm.sqlite3 before doing so.

require_once "../lib/db.php";

$pdo = dbConnect();

$pdo->exec("ALTER TABLE tasks ADD COLUMN due TEXT");
$pdo->exec("ALTER TABLE projects ADD COLUMN due TEXT");

echo "Columns added!\n";
