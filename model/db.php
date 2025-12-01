<?php

try {
    $home = getenv("HOME");
    $db = $home . "/.ppm/ppm.sqlite3";
    $pdo = new PDO("sqlite:$db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
    return null;
}

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

function getTask($tid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    return $task;
}

function updateDescription($tid, $description) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tasks SET description = :description WHERE task_id = :tid");
    $stmt->execute(['description' => $description, 'tid' => $tid]);
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

function getNotesOfProject($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $notes;
}

function getLinksOfProject($pid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM links WHERE project_id = :pid");
    $stmt->execute(['pid' => $pid]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $links;
}

function getNotesOfTask($tid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $notes;
}

function getUpdatesOfTask($tid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM status_updates WHERE task_id = :tid");
    $stmt->execute(['tid' => $tid]);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $updates;
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

function addProject($title, $priority) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO projects (title, priority, status) VALUES (:title, :priority, 'NOT STARTED')");
    $stmt->execute(['title' => $title, 'priority' => $priority]);
}

function addLink($pid, $description, $path) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO links (project_id, description, path) VALUES (:pid, :description, :path)");
    $stmt->execute(['pid' => $pid, 'description' => $description, 'path' => $path]);
}

function updateProjectStatus($pid, $status) {
    global $pdo;
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO status_updates (project_id, status) VALUES (:pid, :status)");
    $stmt->execute(['pid' => $pid, 'status' => $status]);
    $stmt2 = $pdo->prepare("UPDATE projects SET status = :status, updated = CURRENT_TIMESTAMP WHERE project_id = :pid");
    $stmt2->execute(['pid' => $pid, 'status' => $status]);
    if ($status == "COMPLETE" || $status == "ABANDONED") { 
        removeFromQueueByID("project", $pid);
    }
    $pdo->commit();
}

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

function updatePriority($pid, $priority) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE projects SET priority = :priority, updated = CURRENT_TIMESTAMP WHERE project_id = :pid");
    $stmt->execute(['priority' => $priority, 'pid' => $pid]);
}

function getQueue() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM queue ORDER BY position");
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $queue;
}

function addToQueue($type, $id) {
    global $pdo;
    $col = NULL;
    if ($type == "task") {
        $col = "task_id";
    }
    if ($type == "project") {
        $col = "project_id";
    }
    if ($col) {
        $stmt = $pdo->query("SELECT MAX(position) FROM queue;");
        $position = $stmt->fetch(PDO::FETCH_ASSOC)['MAX(position)'] + 1;
        $stmt2 = $pdo->prepare("INSERT INTO queue (position, $col) VALUES (:position, :id)");
        $stmt2->execute(['position' => $position, 'id' => $id]);
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
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function moveInQueue($cur_pos, $target_pos) {
    global $pdo;
    if ($cur_pos == $target_pos || $target_pos < 1) {
        return NULL;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->query("SELECT MAX(position) FROM queue;");
    $last_pos = $stmt->fetch(PDO::FETCH_ASSOC)['MAX(position)'];
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
    $pdo->commit();
}

function removeFromQueueByPosition($pos) { // WRAP IN A TRANSACTION!
    global $pdo;
    $stmt1 = $pdo->prepare("DELETE FROM queue WHERE position = :pos");
    $stmt1->execute(['pos' => $pos]);
    $stmt2 = $pdo->prepare("UPDATE queue SET position = position - 1 WHERE position > :pos");
    $stmt2->execute(['pos' => $pos]);
}

function removeFromQueueByID($type, $id) { // WRAP IN A TRANSACTION!
    global $pdo;
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
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+7 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else if ($num_weeks == 2) {
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+14 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else if ($num_weeks == 3) {
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+21 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else if ($num_weeks == 4) {
        $sql = "SELECT * FROM tasks WHERE due <= date(current_date, '+28 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else {
        $sql = "SELECT * FROM tasks WHERE due IS NOT NULL AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProjectsByDue($num_weeks) {
    global $pdo;
    $sql = "";
    if ($num_weeks == 1) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+7 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else if ($num_weeks == 2) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+14 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else if ($num_weeks == 3) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+21 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else if ($num_weeks == 4) {
        $sql = "SELECT * FROM projects WHERE due <= date(current_date, '+28 day') AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    } else {
        $sql = "SELECT * FROM projects WHERE due IS NOT NULL AND (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY due ASC";
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
