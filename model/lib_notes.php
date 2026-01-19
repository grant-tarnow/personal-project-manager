<?php

function getNotesOfProject($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $notes;
}

function getNotesOfTask($tid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $notes;
}

function addNote($type, $id, $note) {
    global $pdo;
    $col = NULL;
    if ($type == "project") {
        $col = "project_id";
    } else if ($type == "task") {
        $col = "task_id";
    } else {
        throw new Exception('Called addNote with missing or improper $type variable.');
    }
    $stmt = $pdo->prepare("INSERT INTO notes ($col, content) VALUES (:id, :note)");
    $stmt->execute(['id' => $id, 'note' => $note]);
}

