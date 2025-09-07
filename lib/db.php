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
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    return $project;
}

function updateTitle($pdo, $pid, $title) {
    $stmt = $pdo->prepare("UPDATE projects SET title = :title WHERE project_id = :pid");
    $stmt->execute(['title' => $title, 'pid' => $pid]);
}

function getTask($pdo, $tid) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    return $task;
}

function updateDescription($pdo, $tid, $description) {
    $stmt = $pdo->prepare("UPDATE tasks SET description = :description WHERE task_id = :tid");
    $stmt->execute(['description' => $description, 'tid' => $tid]);
}

function getTasksOfProject($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tasks;
}

function getNextOfProject($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = :pid AND next = 1");
    $stmt->execute(['pid' => $pid]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    return $task;
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
    if ($status == "COMPLETE" || $status == "ABANDONED") { 
        removeFromQueueByID($pdo, "project", $pid);
    }
}

function updateTaskStatus($pdo, $tid, $status) {
    $stmt = $pdo->prepare("INSERT INTO status_updates (task_id, status) VALUES (:tid, :status)");
    $stmt->execute(['tid' => $tid, 'status' => $status]);
    $stmt2 = $pdo->prepare("UPDATE tasks SET status = :status, updated = CURRENT_TIMESTAMP WHERE task_id = :tid");
    $stmt2->execute(['tid' => $tid, 'status' => $status]);
    if ($status == "COMPLETE" || $status == "ABANDONED") { 
        unnextify($pdo, $tid);
        removeFromQueueByID($pdo, "task", $tid);
    }
}

function updatePriority($pdo, $pid, $priority) {
    $stmt = $pdo->prepare("UPDATE projects SET priority = :priority, updated = CURRENT_TIMESTAMP WHERE project_id = :pid");
    $stmt->execute(['priority' => $priority, 'pid' => $pid]);
}

function unnextify($pdo, $tid) {
    $stmt = $pdo->prepare("UPDATE tasks SET next = 0 WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
}

function nextify($pdo, $pid, $tid) {
    $tasks = getTasksOfProject($pdo, $pid);
    foreach ($tasks as $task) {
        if ($task['next'] == 1) {
            unnextify($pdo, $task['task_id']);
        }
    }
    $stmt2 = $pdo->prepare("UPDATE tasks SET next = 1 WHERE task_id = :tid");
    $stmt2->execute(['tid' => $tid]);
}

function getQueue($pdo) {
    $stmt = $pdo->query("SELECT * FROM queue ORDER BY position");
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $queue;
}

function addToQueue($pdo, $type, $id) {
    $stmt = $pdo->query("SELECT MAX(position) FROM queue;");
    $position = $stmt->fetch(PDO::FETCH_ASSOC)['MAX(position)'] + 1;
    // will return 1 if nothing is in table
    if ($type == "task") {
        $stmt = $pdo->prepare("INSERT INTO queue (position, task_id) VALUES (:position, :tid)");
        $stmt->execute(['position' => $position, 'tid' => $id]);
    } else if ($type == "project") {
        $stmt = $pdo->prepare("INSERT INTO queue (position, project_id) VALUES (:position, :pid)");
        $stmt->execute(['position' => $position, 'pid' => $id]);
    }
}

function removeFromQueueByID($pdo, $type, $id) {
    if ($type == "task") {
        $stmt = $pdo->prepare("DELETE FROM queue WHERE task_id = :tid");
        $stmt->execute(['tid' => $id]);
    } else if ($type == "project") {
        $stmt = $pdo->prepare("DELETE FROM queue WHERE project_id = :pid");
        $stmt->execute(['pid' => $id]);
    }
}

function checkQueued($pdo, $type, $id) {
    if ($type == "task") {
        $stmt = $pdo->prepare("SELECT * FROM queue WHERE task_id = :tid");
        $stmt->execute(['tid' => $id]);
    } else if ($type == "project") {
        $stmt = $pdo->prepare("SELECT * FROM queue WHERE project_id = :pid");
        $stmt->execute(['pid' => $id]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function moveUp($pdo, $pos) {
    $queue = getQueue($pdo);
    $last_id = null;
    $last_pos = null;
    for ($i = 0; $i < count($queue); $i++) {
        if ($queue[$i]['position'] == $pos) {
            if ($last_id == null) { // early return if item is already top of queue
                return;
            }
            break;
        }
        $last_id = $queue[$i]['id'];
        $last_pos = $queue[$i]['position'];
    }
    $stmt = $pdo->prepare("UPDATE queue SET position = :last WHERE position = :pos");
    $stmt->execute(['last' => $last_pos, 'pos' => $pos]); 
    $stmt2 = $pdo->prepare("UPDATE queue SET position = :pos WHERE id = :id");
    $stmt2->execute(['pos' => $pos, 'id' => $last_id]); 
}

function moveDown($pdo, $pos) {
    $queue = getQueue($pdo);
    $next_id = null;
    $next_pos = null;
    $not_found = true;
    for ($i = 0; $i < count($queue); $i++) {
        $prev_pos = $next_pos;
        $next_id = $queue[$i]['id'];
        $next_pos = $queue[$i]['position'];
        if ($prev_pos == $pos) {
            $not_found = false;;
            break;
        }
    }
    if ($not_found) { // do nothing if item is already bottom of queue
        return;
    }
    $stmt = $pdo->prepare("UPDATE queue SET position = :next WHERE position = :pos");
    $stmt->execute(['next' => $next_pos, 'pos' => $pos]); 
    $stmt2 = $pdo->prepare("UPDATE queue SET position = :pos WHERE id = :id");
    $stmt2->execute(['pos' => $pos, 'id' => $next_id]); 
}

function removeFromQueueByPosition($pdo, $pos) {
    $stmt = $pdo->prepare("DELETE FROM queue WHERE position = :pos");
    $stmt->execute(['pos' => $pos]);
}

?>
