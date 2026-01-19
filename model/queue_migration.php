<?php

require_once "db.php" ;

$pdo->beginTransaction();
$pdo->exec("ALTER TABLE tasks RENAME TO old_tasks");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS tasks (
        task_id INTEGER PRIMARY KEY,
        project_id INTEGER NOT NULL,
        description TEXT NOT NULL,
        status TEXT NOT NULL,
        position INTEGER,
        due TEXT,
        created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (project_id)
            REFERENCES projects (project_id)
            ON DELETE CASCADE
    )
");

$stmt = $pdo->query("SELECT * FROM old_tasks");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tasks as $task) {
    if ($task['status'] == "COMPLETE" || $task['status'] == "ABANDONED") {
        $task['position'] = NULL;
    }
    $stmt2 = $pdo->prepare("INSERT INTO tasks (task_id, project_id, description, status, position, due, created, updated) VALUES (:tid, :pid, :description, :status, :position, :due, :created, :updated)");
    $stmt2->execute(['tid' => $task['task_id'], 'pid' => $task['project_id'], 'description' => $task['description'], 'status' => $task['status'], 'position' => $task['position'], 'due' => $task['due'], 'created' => $task['created'], 'updated' => $task['updated']]);
}

$pdo->exec("DROP TABLE old_tasks");

$stmt3 = $pdo->query("SELECT * FROM projects");
$projects = $stmt3->fetchAll(PDO::FETCH_ASSOC);

foreach ($projects as $project) {
    $prj_tasks = getOpenTasksOfProject($project['project_id']);
    $i = 1;
    foreach ($prj_tasks as $t) {
        $stmt4 = $pdo->prepare("UPDATE tasks SET position = :pos WHERE task_id = :tid");
        $stmt4->execute(['pos' => $i++, 'tid' => $t['task_id']]);
    }
}

$pdo->commit();

echo "Migration complete!\n";

