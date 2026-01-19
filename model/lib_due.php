<?php

function updateTaskDueDate($tid, $date) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tasks SET due = :date WHERE task_id = :tid");
    $stmt->execute(['date' => $date, 'tid' => $tid]);
}

function clearTaskDueDate($tid) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tasks SET due = NULL WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
}

function updateProjectDueDate($pid, $date) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE projects SET due = :date WHERE project_id = :pid");
    $stmt->execute(['date' => $date, 'pid' => $pid]);
}

function clearProjectDueDate($pid) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE projects SET due = NULL WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
}

function getTasksByDue($num_weeks) {
    global $pdo;
    $sql = "";
    if ($num_weeks == 1) {
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+7 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else if ($num_weeks == 2) {
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+14 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else if ($num_weeks == 3) {
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+21 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else if ($num_weeks == 4) {
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+28 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else {
        $sql = "SELECT * FROM tasks WHERE due IS NOT NULL AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProjectsByDue($num_weeks) {
    global $pdo;
    $sql = "";
    if ($num_weeks == 1) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+7 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else if ($num_weeks == 2) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+14 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else if ($num_weeks == 3) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+21 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else if ($num_weeks == 4) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+28 day') AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    } else {
        $sql = "SELECT * FROM projects WHERE due IS NOT NULL AND status != 'COMPLETE' AND status != 'ABANDONED' ORDER BY due ASC";
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
