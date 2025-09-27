<?php

// Run this from the migrations directory
// Make a backup of ppm.sqlite3 before doing so.

require_once "../lib/db.php";

function migrateTask($pdo, $pid, $description, $status, $next, $created, $updated) {
    $stmtx = $pdo->prepare("SELECT MAX(position) FROM tasks WHERE project_id = :pid");
    $stmtx->execute(['pid' => $pid]);
    $position = $stmtx->fetch(PDO::FETCH_ASSOC)['MAX(position)'] + 1;
    $stmty = $pdo->prepare("INSERT INTO tasks (project_id, description, status, position, next, created, updated) VALUES (:pid, :description, :status, :pos, :next, :created, :updated)");
    $stmty->execute(['pid' => $pid, 'description' => $description, 'status' => $status, 'pos' => $position, 'next' => $next, 'created' => $created, 'updated' => $updated]);
}

$pdo = dbConnect();

$pdo->exec("ALTER TABLE tasks RENAME TO tasks_old");
$newtable = 
    'CREATE TABLE IF NOT EXISTS tasks (
        task_id INTEGER PRIMARY KEY,
        project_id INTEGER NOT NULL,
        description TEXT NOT NULL,
        status TEXT NOT NULL,
        position INTEGER NOT NULL,
        next INTEGER DEFAULT 0 NOT NULL,
        created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (project_id)
            REFERENCES projects (project_id)
            ON DELETE CASCADE
    )';
$pdo->exec($newtable);
$stmt = $pdo->query("SELECT * FROM tasks_old ORDER BY task_id ASC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tasks as $task) {
    migrateTask($pdo, $task['project_id'], $task['description'], $task['status'], $task['next'], $task['created'], $task['updated']);
}

$pdo->exec("DROP TABLE IF EXISTS tasks_old");

echo "Migration Complete!\n";
