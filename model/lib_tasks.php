<?php

// Creating Tasks

function addTask($pid, $description) {
    global $pdo;
    $pdo->beginTransaction();
    $stmt1 = $pdo->prepare("SELECT MAX(position) FROM tasks WHERE project_id = :pid");
    $stmt1->execute(['pid' => $pid]);
    $position = $stmt1->fetch(PDO::FETCH_ASSOC)['MAX(position)'] + 1;
    $stmt2 = $pdo->prepare("INSERT INTO tasks (project_id, description, status, position) VALUES (:pid, :description, 'NOT STARTED', :pos)");
    $stmt2->execute(['pid' => $pid, 'description' => $description, 'pos' => $position]);
    $pdo->commit();
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

function updateTaskStatus($tid, $status) {
    global $pdo;
    $pdo->beginTransaction();
    $task = getTask($tid);
    $stmt = $pdo->prepare("INSERT INTO status_updates (task_id, status) VALUES (:tid, :status)");
    $stmt->execute(['tid' => $tid, 'status' => $status]);
    $stmt2 = $pdo->prepare("UPDATE tasks SET status = :status, updated = CURRENT_TIMESTAMP WHERE task_id = :tid");
    $stmt2->execute(['tid' => $tid, 'status' => $status]);
    if ($status == "COMPLETE" || $status == "ABANDONED") { 
        removeFromQueueByID("task", $tid);
        removeTaskFromTaskQueue($task);
    } else if ($task['status'] == "COMPLETE" || $task['status'] == "ABANDONED") {
        addTaskToTaskQueue($tid, $task['project_id']);
    }
    $pdo->commit();
}

function moveTask($tid, $new_pid) {
    global $pdo;
    $task = getTask($tid);
    $prev_pid = $task['project_id'];
    $prev_prj = getProject($prev_pid);
    $new_prj = getProject($new_pid);
    $arrival_note = "TASK TRANSFER: tid:$tid ('{$task['description']}') transferred from pid:$prev_pid ('{$prev_prj['title']}').";
    $departure_note = "TASK TRANSFER: tid:$tid ('{$task['description']}') transferred to pid:$new_pid ('{$new_prj['title']}').";

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE tasks SET project_id = :pid WHERE task_id = :tid");
    $stmt->execute(['pid' => $new_pid, 'tid' => $tid]);
    removeTaskFromTaskQueue($task);
    addTaskToTaskQueue($tid, $new_pid);
    addNote("project", $new_pid, $arrival_note);
    addNote("project", $prev_pid, $departure_note);
    $pdo->commit();
}

// Task Queues within Projects

function addTaskToTaskQueue($tid, $pid) {
    global $pdo;
    $stmt1 = $pdo->prepare("SELECT MAX(position) FROM tasks WHERE project_id = :pid");
    $stmt1->execute(['pid' => $pid]);
    $position = $stmt1->fetch(PDO::FETCH_ASSOC)['MAX(position)'] + 1;
    $stmt2 = $pdo->prepare("UPDATE tasks SET position = :position WHERE task_id = :tid");
    $stmt2->execute(['position' => $position, 'tid' => $tid]);
}

function removeTaskFromTaskQueue($task) { // WRAP IN A TRANSACTION!
    global $pdo;
    if ($task['position']) {
        $stmt1 = $pdo->prepare("UPDATE tasks SET position = NULL WHERE task_id = :tid");
        $stmt1->execute(['tid' => $task['task_id']]);
        $stmt2 = $pdo->prepare("UPDATE tasks SET position = position - 1 WHERE project_id = :pid AND position > :pos");
        $stmt2->execute(['pid' => $task['project_id'], 'pos' => $task['position']]);
    }
}

function updateTaskPosition($tid, $pid, $cur_pos, $target_pos){
    global $pdo;
    if ($cur_pos == $target_pos || $target_pos < 1) {
        return NULL;
    }
    $pdo->beginTransaction();
    $stmt1 = $pdo->query("SELECT MAX(position) FROM tasks WHERE project_id = :pid;");
    $stmt1->execute(['pid' => $pid]);
    $last_pos = $stmt1->fetch(PDO::FETCH_ASSOC)['MAX(position)'];
    if ($target_pos > $last_pos) {
        $pdo->rollBack();
        return NULL;
    }
    if ($target_pos < $cur_pos) {
        $stmt2 = $pdo->prepare("UPDATE tasks SET position = position + 1 WHERE project_id = :pid AND position >= :target_pos AND position < :cur_pos");
    }
    if ($target_pos > $cur_pos) {
        $stmt2 = $pdo->prepare("UPDATE tasks SET position = position - 1 WHERE project_id = :pid AND position <= :target_pos AND position > :cur_pos");
    }
    $stmt2->execute(['pid' => $pid, 'target_pos' => $target_pos, 'cur_pos' => $cur_pos]);
    $stmt3 = $pdo->prepare("UPDATE tasks SET position = :target_pos WHERE task_id = :tid");
    $stmt3->execute(['target_pos' => $target_pos, 'tid' => $tid]);
    $pdo->commit();
}
