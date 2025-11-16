<?php

require_once "../model/db.php";
require_once "../util/utility.php";
require_once "../util/devtools.php";

$action = filter_input(INPUT_POST, "action") ?? filter_input(INPUT_GET, "action") ?? "queues";

if ($action == "queues") {
    $weeks = filter_input(INPUT_GET, "weeks", FILTER_VALIDATE_INT) ?? 2;
    $pos_up = filter_input(INPUT_POST, "pos-up", FILTER_VALIDATE_INT);
    $pos_dn = filter_input(INPUT_POST, "pos-dn", FILTER_VALIDATE_INT);
    $pos_rm = filter_input(INPUT_POST, "pos-rm", FILTER_VALIDATE_INT);
    
    // TODO -- these should be their own actions
    if ($pos_up) {
        moveUp($pos_up);
        header("Location: .?weeks=$weeks");
    }
    if ($pos_dn) {
        moveDown($pos_dn);
        header("Location: .?weeks=$weeks");
    }
    if ($pos_rm) {
        removeFromQueueByPosition($pos_rm);
        header("Location: .?weeks=$weeks");
    }

    $queue = getQueue();
    $projects = getProjectsByDue($weeks);
    $tasks = getTasksByDue($weeks);
    $date_queue = array_merge($projects, $tasks);
    function sort_by_due($a, $b) {
        if ($a['due'] == $b['due']) {
            return 0;
        }
        return ($a['due'] > $b['due'] ? 1 : -1);
    }
    usort($date_queue, "sort_by_due");

    include("queues.php");
}

if ($action == "list-projects") {
    $view = filter_input(INPUT_GET, "view") ?? "default";

    $projects = getProjects($view);

    include("project-list.php");
}

if ($action == "add-project") {
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);
    if ($title && $priority != NULL) {
        addProject($title, $priority);
    }
    header("Location: .?action=list-projects");
}

if ($action == "show-project") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, "pid", FILTER_VALIDATE_INT) ?? 1;

    $project = getProject($pid);
    $tasks = getTasksOfProject($pid);
    $notes = array_reverse(getNotesOfProject($pid));
    $links = getLinksOfProject($pid);
    $complete_tasks = [];
    $incomplete_tasks = [];
    $status_color = statusColor($project['status']);
    foreach ($tasks as $task) {
        if ($task['next'] == 1) { // exclude NEXT task; queried for specifically below
            continue;
        } if ($task['status'] == 'COMPLETE' | $task['status'] == 'ABANDONED') {
            array_push($complete_tasks, $task);
        } else {
            array_push($incomplete_tasks, $task);
        }
    }

    include("project.php");
}

if ($action == "update-project-priority") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($priority != NULL && $note) {
        updatePriority($pid, $priority);
        addNote("project", $pid, $note);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "update-project-due") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $due_date = filter_input(INPUT_POST, "due-date", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($due_date) {
        updateProjectDueDate($pid, $due_date);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "clear-project-due") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($pid) {
        clearProjectDueDate($pid);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "queue-project") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($pid) {
        addToQueue("project", $pid);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "update-project-status") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, "status");
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($status && $note) {
        updateProjectStatus($pid, $status);
        addNote("project", $pid, $note);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "update-project-title") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($title) {
        updateTitle($pid, $title);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "add-link-from-project") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $link_descr = filter_input(INPUT_POST, "link-description", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_path = filter_input(INPUT_POST, "link-path"); // not filtering here...
    if ($link_descr && $link_path) {
        addLink($pid, $link_descr, $link_path);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "add-task") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $description = filter_input(INPUT_POST, "description", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($description) {
        addTask($pid, $description);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "add-note-to-project") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($note) {
        addNote("project", $pid, $note);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "nextify-from-project") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($tid && $pid) {
        nextify($pid, $tid);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "queue-task-from-project") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($tid) {
        addToQueue("task", $tid);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "move-task-up") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($pid && $tid) {
        moveTaskUp($pid, $tid);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "move-task-down") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($pid && $tid) {
        moveTaskDown($pid, $tid);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "show-task") {
    $tid = filter_input(INPUT_GET, "tid", FILTER_VALIDATE_INT);
    $move_task = filter_input(INPUT_GET, "move-task", FILTER_VALIDATE_BOOLEAN) ?? false;

    $task = getTask($tid);
    $pid = $task['project_id'];
    $project = getProject($pid);
    $status_color = statusColor($task['status']);
    $notes = array_reverse(getNotesOfTask($tid));
    $links = getLinksOfProject($pid);

    include("task.php");
}

if ($action == "add-link-from-task") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $link_descr = filter_input(INPUT_POST, "link-description", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_path = filter_input(INPUT_POST, "link-path"); // not filtering here...
    if ($link_descr && $link_path) {
        addLink($pid, $link_descr, $link_path);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "update-task-due") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $due_date = filter_input(INPUT_POST, "due-date", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($due_date) {
        updateTaskDueDate($tid, $due_date);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "clear-task-due") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    if ($tid) {
        clearTaskDueDate($tid);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "nextify") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($tid && $pid) {
        nextify($pid, $tid);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "queue-task") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    if ($tid && $pid) {
        addToQueue("task", $tid);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "update-task-status") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, "status");
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($status && $note) {
        updateTaskStatus($tid, $status);
        addNote("task", $tid, $note);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "move-task") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($tid && $pid) {
        moveTask($tid, $pid);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "update-task-description") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $description = filter_input(INPUT_POST, "description", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($tid && $description) {
        updateDescription($tid, $description);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "add-note-to-task") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($note) {
        addNote("task", $tid, $note);
    }
    header("Location: .?action=show-task&tid=$tid");
}
