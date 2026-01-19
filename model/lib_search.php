<?php

function searchProjects($query) {
    global $pdo;
    $term = "%$query%";
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE title LIKE :term");
    $stmt->execute(['term' => $term]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i = 0; $i < sizeof($projects); $i++) {
        $projects[$i]['title'] = preg_replace(
            "/($query)/i",
            "<span style='color: red;'><b>$1</b></span>",
            $projects[$i]['title']
        );
    }
    return $projects;
}

function searchTasks($query) {
    global $pdo;
    $term = "%$query%";
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE description LIKE :term");
    $stmt->execute(['term' => $term]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i = 0; $i < sizeof($tasks); $i++) {
        $tasks[$i]['description'] = preg_replace(
            "/($query)/i",
            "<span style='color: red;'><b>$1</b></span>",
            $tasks[$i]['description']
        );
    }
    return $tasks;
}

function searchNotes($query) {
    global $pdo;
    $term = "%$query%";
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE content LIKE :term");
    $stmt->execute(['term' => $term]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i = 0; $i < sizeof($notes); $i++) {
        $notes[$i]['content'] = preg_replace(
            "/($query)/i",
            "<span style='color: red;'><b>$1</b></span>",
            $notes[$i]['content']
        );
        if ($notes[$i]['project_id']) {
            $project = getProject($notes[$i]['project_id']);
            $notes[$i]['tod'] = $project['title'];
            $notes[$i]['pot'] = "Project";
            $notes[$i]['parent_url'] = "/?action=show-project&pid={$notes[$i]['project_id']}";
        } else {
            $task = getTask($notes[$i]['task_id']);
            $notes[$i]['tod'] = $task['description'];
            $notes[$i]['pot'] = "Task";
            $notes[$i]['parent_url'] = "/?action=show-task&tid={$notes[$i]['task_id']}";
        }
    }
    return $notes;
}

