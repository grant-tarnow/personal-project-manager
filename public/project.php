<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";
require_once "../lib/utility.php";

$pdo = dbConnect();

$pid = $_GET['pid'] ?? $_POST['pid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_descr = filter_input(INPUT_POST, "link-description", FILTER_SANITIZE_SPECIAL_CHARS);
    // not filtering here...
    $link_path = filter_input(INPUT_POST, "link-path");
    $task_descr = filter_input(INPUT_POST, "task-description", FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, "status"); 
    $status_note = filter_input(INPUT_POST, "status-note", FILTER_SANITIZE_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);
    $priority_note = filter_input(INPUT_POST, "priority-note", FILTER_SANITIZE_SPECIAL_CHARS);
    $tid_for_queue = filter_input(INPUT_POST, "tid-for-queue", FILTER_VALIDATE_INT);
    $pid_for_queue = filter_input(INPUT_POST, "pid-for-queue", FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    $nextify_tid = filter_input(INPUT_POST, "nextify-tid", FILTER_VALIDATE_INT);
    $task_pos_up = filter_input(INPUT_POST, "task-pos-up", FILTER_VALIDATE_INT);
    $task_pos_dn = filter_input(INPUT_POST, "task-pos-dn", FILTER_VALIDATE_INT);

    if ($note) {
        addNote($pdo, "project", $pid, $note);
    }
    if ($link_descr && $link_path) {
        addLink($pdo, $pid, $link_descr, $link_path);
    }
    if ($task_descr) {
        addTask($pdo, $pid, $task_descr);
    }
    if ($status && $status_note) {
        updateProjectStatus($pdo, $pid, $status);
        addNote($pdo, "project", $pid, $status_note);
    }
    if ($priority && $priority_note) {
        updatePriority($pdo, $pid, $priority);
        addNote($pdo, "project", $pid, $priority_note);
    }
    if ($tid_for_queue) {
        addToQueue($pdo, "task", $tid_for_queue);
    }
    if ($pid_for_queue) {
        addToQueue($pdo, "project", $pid_for_queue);
    }
    if ($title) {
        updateTitle($pdo, $pid, $title);
    }
    if ($nextify_tid) {
        nextify($pdo, $pid, $nextify_tid);
    }
    if ($task_pos_up) {
        moveTaskUp($pdo, $pid, $task_pos_up);
    }
    if ($task_pos_dn) {
        moveTaskDown($pdo, $pid, $task_pos_dn);
    }

}

$project = getProject($pdo, $pid);
$tasks = getTasksOfProject($pdo, $pid);
$notes = array_reverse(getNotesOfProject($pdo, $pid));
$links = getLinksOfProject($pdo, $pid);
$complete_tasks = [];
$incomplete_tasks = [];

foreach ($tasks as $task) {
    if ($task['next'] == 1) { // exclude NEXT task; queried for specifically below
        continue;
    }
    if ($task['status'] == 'COMPLETE' | $task['status'] == 'ABANDONED') {
        array_push($complete_tasks, $task);
    } else {
        array_push($incomplete_tasks, $task);
    }
}

?>

<?php include "header.php" ?>

<section class="left">

    <?php
    echo "<h3>Project | <span id='priority'>P{$project['priority']}</span> | <span id='edit'>edit<span></h3>";
    ?>

    <form id="form-priority" action="" method="POST" style="display: none;">
        <label for="priority">Select a priority:</label>
        <select name="priority" required>
            <option value=0>0</option>
            <option value=1>1</option>
            <option value=2>2</option>
            <option value=3 selected>3</option>
            <option value=4>4</option>
            <option value=5>5</option>
        </select>
        <br><br>
        <label for="priority-note">Note:</label>
        <textarea name="priority-note" rows="6" cols="50" required></textarea>
        <br>
        <button type="submit">Save</button>
    </form>

    <form id="form-retitle" action="" method="POST" style="display: none;">
        <label for="title">Title:</label>
        <input type="text" name="title" size="60" value="<?php echo "{$project['title']}" ?>" required>
        <br>
        <button type="submit">Save</button>
    </form>

    <script>
        const form_priority = document.querySelector("#form-priority");
        document.querySelector("#priority").addEventListener("click", function() {
            if (form_priority.style.display == "none") {
                form_priority.style.display = "block";
            } else {
                form_priority.style.display = "none";
            }
        });

        const form_retitle = document.querySelector("#form-retitle");
        document.querySelector("#edit").addEventListener("click", function() {
            if (form_retitle.style.display == "none") {
                form_retitle.style.display = "block";
            } else {
                form_retitle.style.display = "none";
            }
        });
    </script>

    <?php
    $prj_color = statusColor($project['status']);
    echo "<h2>{$project['title']}</h2>";
    echo "<h2><span style='color: $prj_color;'>{$project['status']}</span> | ";
    if (checkQueued($pdo, "project", $pid)) {
        echo "QUEUED";
    } else {
        echo <<<END
        <form action='' method='POST' style='display: inline;'>
        <input type='hidden' name='pid-for-queue' value='$pid'>
        <button type='submit'>queue</button>
        </form>
        END;
    }
    echo "</h2>";
    ?>

    <button type="button" id="btn-status-update">Update Status</button>
    <br><br>
    <form id="form-status-update" action="" method="POST" style="display: none;">
        <label for="status">Select a status:</label>
        <select name="status" id="status" required>
            <option value="NOT STARTED">NOT STARTED</option>
            <option value="IN PROGRESS">IN PROGRESS</option>
            <option value="ON HOLD">ON HOLD</option>
            <option value="ABANDONED">ABANDONED</option>
            <option value="COMPLETE">COMPLETE</option>
        </select>
        <br><br>
        <label for="status-note">Note:</label>
        <textarea name="status-note" rows="6" cols="50" required></textarea>
        <br>
        <button type="submit">Save</button>
    </form>
    <script>
        const btn_status = document.querySelector("#btn-status-update");
        const form_status = document.querySelector("#form-status-update");
        btn_status.addEventListener("click", function() {
            if (form_status.style.display == "none") {
                form_status.style.display = "block";
            } else {
                form_status.style.display = "none";
            }
        });
    </script>

    <hr>

    <h2>Links</h2>

    <?php foreach ($links as $link): ?>
        <?php
        if (filter_var($link['path'], FILTER_VALIDATE_URL)) {
            echo "<p><a href='{$link['path']}' >{$link['description']}</a></p>";
        } else {
            echo "<pre>{$link['description']}\n\t{$link['path']}</pre>";
        }
        ?>
    <?php endforeach; ?>

    <button type="button" id="btn-new-link">New Link</button>
    <br><br>
    <form id="form-new-link" action="" method="POST" style="display: none;">
        <label for="link-description">Description:</label>
        <input type="text" name="link-description" size="40" required>
        <label for="link-path">Path:</label>
        <input type="text" name="link-path" size="40" required>
        <br>
        <button type="submit">Save</button>
    </form>
    <script>
        const btn_link = document.querySelector("#btn-new-link");
        const form_link = document.querySelector("#form-new-link");
        btn_link.addEventListener("click", function() {
            if (form_link.style.display == "none") {
                form_link.style.display = "block";
            } else {
                form_link.style.display = "none";
            }
        });
    </script>


</section>

<section class="center">

    <h2>Task List</h2>

    <button type="button" id="btn-new-task">New Task</button>
    <br><br>
    <form id="form-new-task" action="" method="POST" style="display: none;">
        <label for="task-description">Description:</label>
        <input type="text" name="task-description" size="60" required>
        <br>
        <button type="submit">Save</button>
    </form>
    <script>
        const btn_task = document.querySelector("#btn-new-task");
        const form_task = document.querySelector("#form-new-task");
        btn_task.addEventListener("click", function() {
            if (form_task.style.display == "none") {
                form_task.style.display = "block";
            } else {
                form_task.style.display = "none";
            }
        });
    </script>

    <?php
        $task = getNextOfProject($pdo, $pid);
        if ($task) {
            include "elemTaskCard.php"; // card for NEXT task on top
        }
        foreach ($incomplete_tasks as $task) {
            include "elemTaskCard.php";
        }
    ?>
    <br>
    <button type='button' id='btn-complete-tasks'>Show Complete and Abandoned</button>
    <div id='complete-tasks' style='display: none;'>
        <h2>Complete and Abandoned Tasks</h2>
        <?php
            foreach ($complete_tasks as $task) {
                include "elemTaskCard.php";
            }
        ?>
    </div>
    <script>
        const btn_complete = document.querySelector("#btn-complete-tasks");
        const complete = document.querySelector("#complete-tasks");
        btn_complete.addEventListener("click", function() {
            if (complete.style.display == "none") {
                complete.style.display = "block";
                btn_complete.innerHTML = "Hide Complete and Abandoned";
            } else {
                complete.style.display = "none";
                btn_complete.innerHTML = "Show Complete and Abandoned";
            }
        });
    </script>

</section>

<section class="right">

    <h2>Notes</h2>
    
    <button type="button" id="btn-new-note">New Note</button>
    <br><br>
    <form id="form-new-note" action="" method="POST" style="display: none;">
        <textarea name="note" rows="6" cols="50" required></textarea>
        <br>
        <button type="submit">Save</button>
    </form>
    <script>
        const btn_note = document.querySelector("#btn-new-note");
        const form_note = document.querySelector("#form-new-note");
        btn_note.addEventListener("click", function() {
            if (form_note.style.display == "none") {
                form_note.style.display = "block";
            } else {
                form_note.style.display = "none";
            }
        });
    </script>

    <?php foreach ($notes as $note): ?>
        <?php
        $time = dtEastern($note['created']);
        echo "<h3>$time</h3>";
        echo "<pre>{$note['content']}</pre>";
        ?>
    <?php endforeach; ?>

</section>

<?php include "footer.php" ?>
