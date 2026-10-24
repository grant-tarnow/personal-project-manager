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

function addNote(string $type, int $id, int $num, string $note): void {
    global $pdo;
    $col = NULL;
    if ($type == "project") {
        $col = "project_id";
    } else if ($type == "task") {
        $col = "task_id";
    } else {
        throw new Exception('Called addNote with missing or improper $type variable.');
    }
    $stmt = $pdo->prepare("INSERT INTO notes ($col, note_num, content) VALUES (:id, :num, :note)");
    $stmt->execute(['id' => $id, 'num' => $num, 'note' => $note]);
}

function getNextNoteNum(): int {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM notes ORDER BY note_num DESC LIMIT 1");
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    return $latest['note_num'] + 1;
}

function getNoteHistory($num): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE note_num = :num ORDER BY version DESC");
    $stmt->execute(['num' => $num]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $history;
}

function updateNote(int $num, string $content): void { // Must be used in a transaction!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception('Function updateNote() called oustide of transaction.');
    }
    $history = getNoteHistory($num);
    $latest = $history[0];
    $latest_id = $latest['note_id'];
    $next_version = $latest['version'] + 1;
    $stmt = $pdo->prepare("UPDATE notes SET latest = 0, active = 0, updated = CURRENT_TIMESTAMP WHERE note_id = :id");
    $stmt->execute(['id' => $latest_id]);
    $stmt2 = $pdo->prepare("INSERT INTO notes (note_num, version, project_id, task_id, content) VALUES (:num, :version, :pid, :tid, :content)");
    $stmt2->execute(['num' => $num, 'version' => $next_version, 'pid' => $latest['project_id'], 'tid' => $latest['task_id'], 'content' => $content]);
}

function deactivateNote(int $note_id): void {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notes SET active = 0, updated = CURRENT_TIMESTAMP WHERE note_id = :id");
    $stmt->execute(['id' => $note_id]);
}

function reactivateNote(int $note_id): void {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notes SET active = 1, updated = CURRENT_TIMESTAMP WHERE note_id = :id");
    $stmt->execute(['id' => $note_id]);
}

