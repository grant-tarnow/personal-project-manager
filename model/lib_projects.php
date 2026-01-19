<?php

function getProjects($view = "default") {
    global $pdo;
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
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE (status = 'COMPLETE' OR status = 'ABANDONED') ORDER BY updated DESC");
    } else if ($view == "all") {
        $stmt = $pdo->prepare("SELECT * FROM projects ORDER BY priority");
    }
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $projects;
}

function getProject($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    return $project;
}

function updateTitle($pid, $title) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE projects SET title = :title WHERE project_id = :pid");
    $stmt->execute(['title' => $title, 'pid' => $pid]);
}

function addProject($title, $priority) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO projects (title, priority, status) VALUES (:title, :priority, 'NOT STARTED')");
    $stmt->execute(['title' => $title, 'priority' => $priority]);
}

function updateProjectStatus($pid, $status) { // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("updateProjectStatus() called outside of transaction.");
    }
    $stmt = $pdo->prepare("INSERT INTO status_updates (project_id, status) VALUES (:pid, :status)");
    $stmt->execute(['pid' => $pid, 'status' => $status]);
    $stmt2 = $pdo->prepare("UPDATE projects SET status = :status, updated = CURRENT_TIMESTAMP WHERE project_id = :pid");
    $stmt2->execute(['pid' => $pid, 'status' => $status]);
}

function updatePriority($pid, $priority) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE projects SET priority = :priority, updated = CURRENT_TIMESTAMP WHERE project_id = :pid");
    $stmt->execute(['priority' => $priority, 'pid' => $pid]);
}

function getLinksOfProject($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM links WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $links;
}

function addLink($pid, $description, $path) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO links (project_id, description, path) VALUES (:pid, :description, :path)");
    $stmt->execute(['pid' => $pid, 'description' => $description, 'path' => $path]);
}

