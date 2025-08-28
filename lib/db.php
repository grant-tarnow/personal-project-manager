<?php

function dbConnect() {
    try {
        $home = getenv("HOME");
        $db = $home . "/.ppm/ppm.sqlite3";
        $pdo = new PDO("sqlite:$db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo $e->getMessage();
        return null;
    }
}

function getProjects($pdo, $view = "default") {
    $stmt = "";
    if ($view == "default") {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY priority");
    } else if ($view == "active") {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status = 'IN PROGRESS') ORDER BY priority");
    } else if ($view == "hold") {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status = 'ON HOLD') ORDER BY priority");
    } else if ($view == "incomplete") {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status != 'COMPLETE' AND status != 'ABANDONED') ORDER BY priority");
    } else if ($view == "complete") {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status = 'COMPLETE' OR status = 'ABANDONED') ORDER BY priority");
    } else if ($view == "all") {
        $stmt = $pdo->prepare("SELECT * FROM projects ORDER BY priority");
    }
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $projects;
}

function getProject($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $project = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $project;
}

function getTask($pdo, $tid) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $task = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $task;
}

function getTasksOfProject($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $project = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $project;
}

function getNotesOfProject($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $notes;
}

function getLinksOfProject($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT * FROM links WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $links;
}

function getNotesOfTask($pdo, $tid) {
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $notes;
}

function getUpdatesOfTask($pdo, $tid) {
    $stmt = $pdo->prepare("SELECT * FROM status_updates WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $updates;
}

// TODO -> Error reporting?
function addNote($pdo, $type, $id, $note) {
    $stmt = "";
    if ($type == "project") {
        $stmt = $pdo->prepare("INSERT INTO notes (project_id, content) VALUES (:id, :note)");
    } else if ($type == "task") {
        $stmt = $pdo->prepare("INSERT INTO notes (task_id, content) VALUES (:id, :note)");
    } else {
        throw new Exception('Called addNote with missing or improper $type variable.');
    }
    $stmt->execute(['id' => $id, 'note' => $note]);
}

// TODO -> Error reporting?
function addTask($pdo, $pid, $description) {
    $stmt = $pdo->prepare("INSERT INTO tasks (project_id, description, status) VALUES (:pid, :description, 'NOT STARTED')");
    $stmt->execute(['pid' => $pid, 'description' => $description]);
}

// TODO -> Error reporting?
function addProject($pdo, $title, $priority) {
    $stmt = $pdo->prepare("INSERT INTO projects (title, priority, status) VALUES (:title, :priority, 'NOT STARTED')");
    $stmt->execute(['title' => $title, 'priority' => $priority]);
}

// TODO -> Error reporting?
function addLink($pdo, $pid, $description, $path) {
    $stmt = $pdo->prepare("INSERT INTO links (project_id, description, path) VALUES (:pid, :description, :path)");
    $stmt->execute(['pid' => $pid, 'description' => $description, 'path' => $path]);
}

function updateProjectStatus($pdo, $pid, $status) {
    $stmt = $pdo->prepare("INSERT INTO status_updates (project_id, status) VALUES (:pid, :status)");
    $stmt->execute(['pid' => $pid, 'status' => $status]);
    $stmt2 = $pdo->prepare("UPDATE projects SET status = :status, updated = CURRENT_TIMESTAMP WHERE project_id = :pid");
    $stmt2->execute(['pid' => $pid, 'status' => $status]);
}

function updateTaskStatus($pdo, $tid, $status) {
    $stmt = $pdo->prepare("INSERT INTO status_updates (task_id, status) VALUES (:tid, :status)");
    $stmt->execute(['tid' => $tid, 'status' => $status]);
    $stmt2 = $pdo->prepare("UPDATE tasks SET status = :status, updated = CURRENT_TIMESTAMP WHERE task_id = :tid");
    $stmt2->execute(['tid' => $tid, 'status' => $status]);
    if ($status == "COMPLETE" || $status == "ABANDONED") { 
        $stmt3 = $pdo->prepare("UPDATE tasks SET next = 0 WHERE task_id = :tid");
        $stmt3->execute(['tid' => $tid]);
    }
}

function updatePriority($pdo, $pid, $priority) {
    $stmt = $pdo->prepare("UPDATE projects SET priority = :priority, updated = CURRENT_TIMESTAMP WHERE project_id = :pid");
    $stmt->execute(['priority' => $priority, 'pid' => $pid]);
}

function nextify($pdo, $pid, $tid) {
    $tasks = getTasksOfProject($pdo, $pid);
    foreach ($tasks as $task) {
        if ($task['next'] == 1) {
            $stmt = $pdo->prepare("UPDATE tasks SET next = 0 WHERE task_id = :tid");
            $stmt->execute(['tid' => $task['task_id']]);
        }
    }
    $stmt2 = $pdo->prepare("UPDATE tasks SET next = 1 WHERE task_id = :tid");
    $stmt2->execute(['tid' => $tid]);
}

?>
