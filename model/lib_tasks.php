<?php

// Creating Tasks

function addTask($pid, $description) { // WRAP IN A TRANSACTION
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("addTask() called outside of transaction.");
    }
    $max_pos = getMaxPos($pid);
    $stmt = $pdo->prepare("INSERT INTO tasks (project_id, description, status, position) VALUES (:pid, :description, 'NOT STARTED', :pos)");
    $stmt->execute(['pid' => $pid, 'description' => $description, 'pos' => $max_pos + 1]);
}

// Getting Tasks

function getTask($tid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    return $task;
}

function getUpdatesOfTask($tid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM status_updates WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $updates;
}

function getOpenTasksOfProject($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = :pid AND position IS NOT NULL ORDER BY position ASC");
    $stmt->execute(['pid' => $pid]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tasks;
}

function getClosedTasksOfProject($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = :pid AND position IS NULL");
    $stmt->execute(['pid' => $pid]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tasks;
}

// Updating Tasks

function updateDescription($tid, $description) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tasks SET description = :description WHERE task_id = :tid");
    $stmt->execute(['description' => $description, 'tid' => $tid]);
}

function updateTaskStatus($tid, $status) { // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("updateTaskStatus() called outside of transaction.");
    }
    $stmt = $pdo->prepare("INSERT INTO status_updates (task_id, status) VALUES (:tid, :status)");
    $stmt->execute(['tid' => $tid, 'status' => $status]);
    $stmt2 = $pdo->prepare("UPDATE tasks SET status = :status, updated = CURRENT_TIMESTAMP WHERE task_id = :tid");
    $stmt2->execute(['tid' => $tid, 'status' => $status]);
}

function transferTask($tid, $pid) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tasks SET project_id = :pid WHERE task_id = :tid");
    $stmt->execute(['pid' => $new_pid, 'tid' => $tid]);
}

// Task Queues within Projects

function getMaxPos($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT MAX(position) FROM tasks WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC)['MAX(position)'];
    if (!$position) {
        $position = 0;
    }
    return $position;
}

function addTaskToTaskQueue($tid, $pid) { // WRAP IN A TRANSACTION
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("addTaskToTaskQueue() called outside of transaction.");
    }
    $max_pos = getMaxPos($pid);
    $stmt = $pdo->prepare("UPDATE tasks SET position = :position WHERE task_id = :tid");
    $stmt->execute(['position' => $max_pos + 1, 'tid' => $tid]);
}

function removeTaskFromTaskQueue($task) { // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("removeTaskFromTaskQueue() called outside of transaction.");
    }
    if ($task['position']) {
        $stmt1 = $pdo->prepare("UPDATE tasks SET position = NULL WHERE task_id = :tid");
        $stmt1->execute(['tid' => $task['task_id']]);
        $stmt2 = $pdo->prepare("UPDATE tasks SET position = position - 1 WHERE project_id = :pid AND position > :pos");
        $stmt2->execute(['pid' => $task['project_id'], 'pos' => $task['position']]);
    }
}

function updateTaskPosition($tid, $pid, $cur_pos, $target_pos){ // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("updateTaskPosition() called outside of transaction.");
    }
    if ($cur_pos == $target_pos || $target_pos < 1) {
        return NULL;
    }
    $last_pos = getMaxPos($pid);
    if ($target_pos > $last_pos) {
        $pdo->rollBack();
        return NULL;
    }
    if ($target_pos < $cur_pos) {
        $stmt1 = $pdo->prepare("UPDATE tasks SET position = position + 1 WHERE project_id = :pid AND position >= :target_pos AND position < :cur_pos");
    }
    if ($target_pos > $cur_pos) {
        $stmt1 = $pdo->prepare("UPDATE tasks SET position = position - 1 WHERE project_id = :pid AND position <= :target_pos AND position > :cur_pos");
    }
    $stmt1->execute(['pid' => $pid, 'target_pos' => $target_pos, 'cur_pos' => $cur_pos]);
    $stmt2 = $pdo->prepare("UPDATE tasks SET position = :target_pos WHERE task_id = :tid");
    $stmt2->execute(['target_pos' => $target_pos, 'tid' => $tid]);
}

