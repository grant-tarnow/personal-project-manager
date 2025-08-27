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
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status != 'COMPLETE') ORDER BY priority");
    } else if ($view == "complete") {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status = 'COMPLETE' or status = 'ABANDONED') ORDER BY priority");
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
function addLink($pdo, $pid, $description, $path) {
    $stmt = $pdo->prepare("INSERT INTO links (project_id, description, path) VALUES (:pid, :description, :path)");
    $stmt->execute(['pid' => $pid, 'description' => $description, 'path' => $path]);
}

?>
