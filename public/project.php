<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";
require_once "../lib/utility.php";

$pdo = dbConnect();

$pid = $_GET['pid'] ?? $_POST['pid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_descr = filter_input(INPUT_POST, "link-description", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_path = filter_input(INPUT_POST, "link-path"); // not filtering here...
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
    $due_date = filter_input(INPUT_POST, "due-date", FILTER_SANITIZE_SPECIAL_CHARS);
    $clear_due_date = filter_input(INPUT_POST, "clear-due-date", FILTER_VALIDATE_BOOLEAN);

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
    if ($due_date) {
        updateProjectDueDate($pdo, $pid, $due_date);
    }
    if ($clear_due_date) {
        clearProjectDueDate($pdo, $pid);
    }

}

$project = getProject($pdo, $pid);
$tasks = getTasksOfProject($pdo, $pid);
$notes = array_reverse(getNotesOfProject($pdo, $pid));
$links = getLinksOfProject($pdo, $pid);
$complete_tasks = [];
$incomplete_tasks = [];

$status_color = statusColor($project['status']);

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

<style>
    .project {
        display: flex;
        flex-flow: row nowrap;
        justify-content: space-between;
        gap: 50px;
        width: 100%;
    }
    .details {
        flex: 2;
    }
    .tasks {
        flex: 3;
    }
    .notes {
        flex: 4;
    }
    #status-display {
        color: <?= $status_color ?>;
    } #status-display:hover {
        cursor: pointer;
    }
    #priority:hover {
        cursor: pointer;
    }
</style>

<main class="project">
    <section class="details">
        <h3>Project | <span id='priority'>P<?= $project['priority'] ?></span></h3>
        <form id="form-priority" action="" method="POST" hidden>
            <label for="priority" style="display: block;">Select a priority:</label>
            <select name="priority" required>
                <option value=0>0</option>
                <option value=1>1</option>
                <option value=2>2</option>
                <option value=3 selected>3</option>
                <option value=4>4</option>
                <option value=5>5</option>
            </select>
            <br>
            <br>
            <label for="priority-note" style="display: block;">Note:</label>
            <textarea name="priority-note" rows="6" style="width: 100%;" required></textarea>
            <button type="submit">Save</button>
        </form>
        <script>
            const form_priority = document.querySelector("#form-priority");
            document.querySelector("#priority").addEventListener("click", function() {
                if (form_priority.hidden) {
                    form_priority.hidden = false;
                } else {
                    form_priority.hidden = true;
                }
            });
        </script>
        <h2><?= $project['title'] ?></h2>
        <h3><span class='due-date-display'>Due date: <?= $project['due'] ? $project['due'] : "None" ?></span></h3>
        <div id="due-date-div" hidden>
            <form action="" method="POST">
                <input type='hidden' name='pid' value=<?= $pid ?> />
                <label for='due-date'>Enter due date:</label>
                <input type='date' id='due-date' name='due-date' <?= $project['due'] ? "value='{$project['due']}'" : "" ?>/>
                <button type='submit'>Save</button>
            </form>
            <form action="" method="POST">
                <input type='hidden' name='pid' value=<?= $pid ?> />
                <input type='hidden' name='clear-due-date' value='true' />
                <button type='submit' class="solo-btn">Clear</button>
            </form>
        </div>
        <script>
            const due_date = document.querySelector(".due-date-display");
            const due_date_div = document.querySelector("#due-date-div");
            due_date.addEventListener("click", function(e){
                if (due_date_div.hidden) {
                    due_date_div.hidden = false;
                } else {
                    due_date_div.hidden = true;
                }
            });
        </script>
        <h2><span id="status-display"><?= $project['status'] ?></span>
        |
        <?php if (checkQueued($pdo, "project", $pid)): ?>
        QUEUED
        <?php else: ?>
            <form action='' method='POST' style='display: inline;'>
            <input type='hidden' name='pid-for-queue' value=<?= $pid ?>>
            <button type='submit' class="solo-btn">queue</button>
            </form>
        <?php endif; ?>
        </h2>
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
            <textarea name="status-note" rows="6" style="width: 100%;" required></textarea>
            <button type="submit">Save</button>
        </form>
        <script>
            const status_display = document.querySelector("#status-display");
            const form_status = document.querySelector("#form-status-update");
            status_display.addEventListener("click", function() {
                if (form_status.style.display == "none") {
                    form_status.style.display = "block";
                } else {
                    form_status.style.display = "none";
                }
            });
        </script>
        <hr>
        <button id="btn-update-title" type="button" style="margin: 10px;">Update title</button>
        <br>
        <form id="form-retitle" action="" method="POST" hidden>
            <label for="title" style="display: block;">Title:</label>
            <input type="text" name="title" style="width: 100%;" value="<?= $project['title'] ?>" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const form_retitle = document.querySelector("#form-retitle");
            document.querySelector("#btn-update-title").addEventListener("click", function() {
                if (form_retitle.hidden) {
                    form_retitle.hidden = false;
                } else {
                    form_retitle.hidden = true;
                }
            });
        </script>
        <hr>
        <h2>Links</h2>
        <?php foreach ($links as $link): ?>
            <?php if (filter_var($link['path'], FILTER_VALIDATE_URL)): ?>
                <p><a href='<?= $link['path'] ?>'><?= $link['description'] ?></a></p>
            <?php else: ?>
                <pre><?= "{$link['description']}\n\t{$link['path']}" ?></pre>
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="button" id="btn-new-link">New Link</button>
        <br>
        <br>
        <form id="form-new-link" action="" method="POST" hidden>
            <label for="link-description" style="display: block;">Description:</label>
            <input type="text" name="link-description" style="width: 100%;" required>
            <label for="link-path" style="display: block;">Path:</label>
            <input type="text" name="link-path" style="width: 100%;" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const btn_link = document.querySelector("#btn-new-link");
            const form_link = document.querySelector("#form-new-link");
            btn_link.addEventListener("click", function() {
                if (form_link.hidden) {
                    form_link.hidden = false;
                } else {
                    form_link.hidden = true;
                }
            });
        </script>
    </section>

    <section class="tasks">
        <h2>Task List</h2>
        <button type="button" id="btn-new-task">New Task</button>
        <br>
        <br>
        <form id="form-new-task" action="" method="POST" hidden>
            <label for="task-description" style="display: block;">Description:</label>
            <input type="text" name="task-description" style="width: 100%;" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const btn_task = document.querySelector("#btn-new-task");
            const form_task = document.querySelector("#form-new-task");
            btn_task.addEventListener("click", function() {
                if (form_task.hidden) {
                    form_task.hidden = false;
                } else {
                    form_task.hidden = true;
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
        <div id='complete-tasks' hidden>
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
                if (complete.hidden) {
                    complete.hidden = false;
                    btn_complete.innerHTML = "Hide Complete and Abandoned";
                } else {
                    complete.hidden = true;
                    btn_complete.innerHTML = "Show Complete and Abandoned";
                }
            });
        </script>
    </section>

    <section class="notes">
        <h2>Notes</h2>
        <button type="button" id="btn-new-note">New Note</button>
        <br>
        <br>
        <form id="form-new-note" action="" method="POST" hidden>
            <textarea name="note" rows="6" style="width: 100%;" required></textarea>
            <button type="submit">Save</button>
        </form>
        <script>
            const btn_note = document.querySelector("#btn-new-note");
            const form_note = document.querySelector("#form-new-note");
            btn_note.addEventListener("click", function() {
                if (form_note.hidden) {
                    form_note.hidden = false;
                } else {
                    form_note.hidden = true;
                }
            });
        </script>
        <?php foreach ($notes as $note): ?>
            <?php
            $time = dtEastern($note['created']);
            $content = preg_replace("/(pid:(\d+))/", "<a href='/project.php?pid=$2'>$1</a>", $note['content']);
            $content = preg_replace("/(tid:(\d+))/", "<a href='/task.php?tid=$2'>$1</a>", $content);
            ?>
            <h3><?= $time ?></h3>
            <pre><?= $content ?></pre>
        <?php endforeach; ?>
    </section>

</main>

<?php include "footer.php" ?>
