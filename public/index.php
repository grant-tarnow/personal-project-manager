<?php

require_once "../model/db.php";
require_once "../model/lib_projects.php";
require_once "../model/lib_tasks.php";
require_once "../model/lib_notes.php";
require_once "../model/lib_queue.php";
require_once "../model/lib_due.php";
require_once "../model/lib_search.php";
require_once "../model/lib_ppm.php";
require_once "../util/utility.php";
require_once "../util/devtools.php";

$action = filter_input(INPUT_POST, "action") ?? filter_input(INPUT_GET, "action") ?? "queues";

if ($action == "queues") {
    $weeks = filter_input(INPUT_GET, "weeks", FILTER_VALIDATE_INT) ?? 2;
    $pos_up = filter_input(INPUT_POST, "pos-up", FILTER_VALIDATE_INT);
    $pos_dn = filter_input(INPUT_POST, "pos-dn", FILTER_VALIDATE_INT);
    $pos_rm = filter_input(INPUT_POST, "pos-rm", FILTER_VALIDATE_INT);
    $selected_pos = filter_input(INPUT_POST, "selected-pos", FILTER_VALIDATE_INT);
    $current_pos = filter_input(INPUT_POST, "current-pos", FILTER_VALIDATE_INT);
    
    // TODO -- these should be their own actions
    // Maybe not, cause it's working fine.
    if ($pos_rm) {
        $pdo->beginTransaction();
        removeFromQueueByPosition($pos_rm);
        $pdo->commit();
        header("Location: .?weeks=$weeks");
    }
    if ($selected_pos && $current_pos) {
        $pdo->beginTransaction();
        moveInQueue($current_pos, $selected_pos);
        $pdo->commit();
        header("Location: .?weeks=$weeks");
    }

    $queue = getQueue();
    $projects_due = getProjectsByDue($weeks);
    $tasks_due = getTasksByDue($weeks);
    $date_queue = array_merge($tasks_due, $projects_due);
    function sort_by_due($a, $b) {
        if ($a['due'] == $b['due']) {
            return 0;
        }
        return ($a['due'] > $b['due'] ? 1 : -1);
    }
    usort($date_queue, "sort_by_due");
    $projects = getProjects("active");

    include("../ppm/queues.php");
}

if ($action == "queue-from-date-queue") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($tid) {
        $pdo->beginTransaction();
        addToQueue("task", $tid);
        $pdo->commit();
    }
    if ($pid) {
        $pdo->beginTransaction();
        addToQueue("project", $pid);
        $pdo->commit();
    }
    header("Location: .?action=queues");
}

if ($action == "list-projects") {
    $view = filter_input(INPUT_GET, "view") ?? "default";
    $projects = getProjects($view);
    include("../ppm/project-list.php");
}

if ($action == "add-project") {
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);
    if ($title && $priority !== NULL) {
        addProject($title, $priority);
    }
    header("Location: .?action=list-projects");
}

if ($action == "show-project") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, "pid", FILTER_VALIDATE_INT) ?? 1;
    $project = getProject($pid);
    $notes = array_reverse(getNotesOfProject($pid));
    $links = getLinksOfProject($pid);
    $complete_tasks = getClosedTasksOfProject($pid);
    $incomplete_tasks = getOpenTasksOfProject($pid);
    $status_color = statusColor($project['status']);
    include("../ppm/project.php");
}

if ($action == "update-project-priority") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($pid && $priority !== NULL && $note) {
        $pdo->beginTransaction();
        updatePriority($pid, $priority);
        addNote("project", $pid, $note);
        $pdo->commit();
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "update-project-due") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $due_date = filter_input(INPUT_POST, "due-date", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($pid && $due_date) {
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
        $pdo->beginTransaction();
        addToQueue("project", $pid);
        $pdo->commit();
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "update-project-status") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, "status");
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($pid && $status && $note) {
        $pdo->beginTransaction();
        updateProjectStatus($pid, $status);
        addNote("project", $pid, $note);
        if ($status == "COMPLETE" || $status == "ABANDONED") { 
            removeFromQueueByID("project", $pid);
        }
        $pdo->commit();
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "update-project-title") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($pid && $title) {
        updateTitle($pid, $title);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "add-link-from-project") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $link_descr = filter_input(INPUT_POST, "link-description", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_path = filter_input(INPUT_POST, "link-path"); // not filtering here.
    // link_path may be URL or may be text. Need to figure out what filter to use.
    if ($pid && $link_descr && $link_path) {
        addLink($pid, $link_descr, $link_path);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "add-task") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $description = filter_input(INPUT_POST, "description", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($pid && $description) {
        $pdo->beginTransaction();
        addTask($pid, $description);
        $pdo->commit();
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "update-task-position") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $selected_pos = filter_input(INPUT_POST, "selected-pos", FILTER_VALIDATE_INT);
    $current_pos = filter_input(INPUT_POST, "current-pos", FILTER_VALIDATE_INT);
    if ($tid && $pid && $selected_pos && $current_pos) {
        $pdo->beginTransaction();
        updateTaskPosition($tid, $pid, $current_pos, $selected_pos);
        $pdo->commit();
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "add-note-to-project") {
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($pid && $note) {
        addNote("project", $pid, $note);
    }
    header("Location: .?action=show-project&pid=$pid");
}

if ($action == "queue-task-from-project") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($tid) {
        $pdo->beginTransaction();
        addToQueue("task", $tid);
        $pdo->commit();
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
    include("../ppm/task.php");
}

if ($action == "add-link-from-task") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    $link_descr = filter_input(INPUT_POST, "link-description", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_path = filter_input(INPUT_POST, "link-path"); // not filtering here.
    // link_path may be URL or may be text. Need to figure out what filter to use.
    if ($pid && $link_descr && $link_path) {
        addLink($pid, $link_descr, $link_path);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "update-task-due") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $due_date = filter_input(INPUT_POST, "due-date", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($tid && $due_date) {
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

if ($action == "queue-task") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    if ($tid) {
        $pdo->beginTransaction();
        addToQueue("task", $tid);
        $pdo->commit();
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "update-task-status") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, "status");
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    if ($tid && $status && $note) {
        $pdo->beginTransaction();
        $task = getTask($tid);
        updateTaskStatus($tid, $status);
        addNote("task", $tid, $note);
        if ($status == "COMPLETE" || $status == "ABANDONED") { 
            removeFromQueueByID("task", $tid);
            removeTaskFromTaskQueue($task);
        } else if ($task['status'] == "COMPLETE" || $task['status'] == "ABANDONED") {
            addTaskToTaskQueue($tid, $task['project_id']);
        }
        $pdo->commit();
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "move-task") {
    $tid = filter_input(INPUT_POST, "tid", FILTER_VALIDATE_INT);
    $pid = filter_input(INPUT_POST, "pid", FILTER_VALIDATE_INT);
    if ($tid && $pid) {
        $pdo->beginTransaction();
        $task = getTask($tid);
        $prev_pid = $task['project_id'];
        $prev_prj = getProject($prev_pid);
        $new_prj = getProject($pid);
        $arrival_note = "TASK TRANSFER: tid:$tid ('{$task['description']}') transferred from pid:$prev_pid ('{$prev_prj['title']}').";
        $departure_note = "TASK TRANSFER: tid:$tid ('{$task['description']}') transferred to pid:$pid ('{$new_prj['title']}').";
        transferTask($tid, $pid);
        removeTaskFromTaskQueue($task);
        addTaskToTaskQueue($tid, $pid);
        addNote("project", $prev_pid, $departure_note);
        addNote("project", $pid, $arrival_note);
        $pdo->commit();
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
    if ($tid && $note) {
        addNote("task", $tid, $note);
    }
    header("Location: .?action=show-task&tid=$tid");
}

if ($action == "search") {
    $query = filter_input(INPUT_GET, "query", FILTER_SANITIZE_SPECIAL_CHARS) ?? "";
    $search_projects = filter_input(INPUT_GET, "search-projects");
    $search_tasks = filter_input(INPUT_GET, "search-tasks");
    $search_notes = filter_input(INPUT_GET, "search-notes");
    $check_project = "checked";
    $check_task = "checked";
    $check_note = "checked";
    if ($query) {
        if ($search_projects) {
            $projects = searchProjects($query);
        } else {
            $check_project = "";
        }
        if ($search_tasks) {
            $tasks = searchTasks($query);
        } else {
            $check_task = "";
        }
        if ($search_notes) {
            $notes = searchNotes($query);
        } else {
            $check_note = "";
        }
    }
    include("../ppm/search.php");
}

