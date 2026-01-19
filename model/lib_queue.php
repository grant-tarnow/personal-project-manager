<?php

function getMaxQueuePos() {
    $stmt = $pdo->query("SELECT MAX(position) FROM queue;");
    $position = $stmt->fetch(PDO::FETCH_ASSOC)['MAX(position)'];
    if (!$position) {
        $position = 0;
    }
    return $position;
}

function getQueue() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM queue ORDER BY position");
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $queue;
}

function addToQueue($type, $id) { // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("addToQueue() called outside of transaction.");
    }
    $col = NULL;
    if ($type == "task") {
        $col = "task_id";
    }
    if ($type == "project") {
        $col = "project_id";
    }
    if ($col) {
        $max_pos = getMaxQueuePos();
        $stmt = $pdo->prepare("INSERT INTO queue (position, $col) VALUES (:position, :id)");
        $stmt->execute(['position' => $max_pos + 1, 'id' => $id]);
    }
}

function checkQueued($type, $id) {
    global $pdo;
    if ($type == "task") {
        $col = "task_id";
    }
    if ($type == "project") {
        $col = "project_id";
    }
    if ($col) {
        $stmt = $pdo->prepare("SELECT * FROM queue WHERE $col = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
    return NULL;
}

function moveInQueue($cur_pos, $target_pos) { // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("moveInQueue() called outside of transaction.");
    }
    if ($cur_pos == $target_pos || $target_pos < 1) {
        return NULL;
    }
    $last_pos = getMaxQueuePos();
    if ($target_pos > $last_pos) {
        $pdo->rollBack();
        return NULL;
    }
    $stmt1 = $pdo->prepare("SELECT id FROM queue WHERE position = :cur_pos");
    $stmt1->execute(['cur_pos' => $cur_pos]);
    $id = $stmt1->fetch(PDO::FETCH_ASSOC)['id'];
    if ($target_pos < $cur_pos) {
        $stmt2 = $pdo->prepare("UPDATE queue SET position = position + 1 WHERE position >= :target_pos AND position < :cur_pos");
    }
    if ($target_pos > $cur_pos) {
        $stmt2 = $pdo->prepare("UPDATE queue SET position = position - 1 WHERE position <= :target_pos AND position > :cur_pos");
    }
    $stmt2->execute(['target_pos' => $target_pos, 'cur_pos' => $cur_pos]);
    $stmt3 = $pdo->prepare("UPDATE queue SET position = :target_pos WHERE id = :id");
    $stmt3->execute(['target_pos' => $target_pos, 'id' => $id]);
}

function removeFromQueueByPosition($pos) { // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("removeFromQueueByPosition() called outside of transaction.");
    }
    $stmt1 = $pdo->prepare("DELETE FROM queue WHERE position = :pos");
    $stmt1->execute(['pos' => $pos]);
    $stmt2 = $pdo->prepare("UPDATE queue SET position = position - 1 WHERE position > :pos");
    $stmt2->execute(['pos' => $pos]);
}

function removeFromQueueByID($type, $id) { // WRAP IN A TRANSACTION!
    global $pdo;
    if (!$pdo->inTransaction()) {
        throw new Exception("removeFromQueueByID() called outside of transaction.");
    }
    $col = NULL;
    if ($type == "task") {
        $col = "task_id";
    }
    if ($type == "project") {
        $col = "project_id";
    }
    if ($col) {
        $stmt = $pdo->prepare("SELECT position FROM queue WHERE $col = :id");
        $stmt->execute(['id' => $id]);
        $pos = $stmt->fetch(PDO::FETCH_ASSOC)['position'];
        if ($pos) {
            removeFromQueueByPosition($pos);
        }
    }
}

