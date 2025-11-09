<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";
require_once "../lib/utility.php";

$pdo = dbConnect();

$tid = $_GET['tid'] ?? $_POST['tid'];
$task = getTask($pdo, $tid);
$pid = $task['project_id'];
$project = getProject($pdo, $pid);

$move_task = $_GET['move-task'] ?? false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $note = filter_input(INPUT_POST, "note", FILTER_SANITIZE_SPECIAL_CHARS);
    $link_descr = filter_input(INPUT_POST, "link-description", FILTER_SANITIZE_SPECIAL_CHARS);
    // not filtering here...
    $link_path = filter_input(INPUT_POST, "link-path");
    $status = filter_input(INPUT_POST, "status"); 
    $status_note = filter_input(INPUT_POST, "status-note", FILTER_SANITIZE_SPECIAL_CHARS);
    $nextify = filter_input(INPUT_POST, "nextify", FILTER_VALIDATE_BOOLEAN);
    $tid_for_queue = filter_input(INPUT_POST, "tid-for-queue", FILTER_VALIDATE_INT);
    $description = filter_input(INPUT_POST, "description", FILTER_SANITIZE_SPECIAL_CHARS);
    $move_to_pid = filter_input(INPUT_POST, "move-to-pid", FILTER_VALIDATE_INT);
    $due_date = filter_input(INPUT_POST, "due-date", FILTER_SANITIZE_SPECIAL_CHARS);
    $clear_due_date = filter_input(INPUT_POST, "clear-due-date", FILTER_VALIDATE_BOOLEAN);

    if ($note) {
        addNote($pdo, "task", $tid, $note);
    }
    if ($link_descr && $link_path) {
        addLink($pdo, $pid, $link_descr, $link_path);
    }
    if ($status && $status_note) {
        updateTaskStatus($pdo, $tid, $status);
        addNote($pdo, "task", $tid, $status_note);
    }
    if ($nextify) {
        nextify($pdo, $pid, $tid);
    }
    if ($tid_for_queue) {
        addToQueue($pdo, "task", $tid_for_queue);
    }
    if ($description) {
        updateDescription($pdo, $tid, $description);
    }
    if ($move_to_pid) {
        moveTask($pdo, $tid, $move_to_pid);
    }
    if ($due_date) {
        updateTaskDueDate($pdo, $tid, $due_date);
    }
    if ($clear_due_date) {
        clearTaskDueDate($pdo, $tid);
    }

}

// need to fetch these again after updates to render correctly after a POST
$task = getTask($pdo, $tid);
$pid = $task['project_id'];
$project = getProject($pdo, $pid);
$status_color = statusColor($task['status']);

$notes = array_reverse(getNotesOfTask($pdo, $tid));
$links = getLinksOfProject($pdo, $pid);

?>

<?php include "header.php" ?>

<style>
    .task {
        display: flex;
        flex-flow: row nowrap;
        justify-content: space-between;
        gap: 50px;
        width: 100%;
    }
    .project {
        flex: 1;
    }
    .details {
        flex: 2;
    }
    .notes {
        flex: 2;
    }
    #status-display {
        color: <?= $status_color ?>;
    } #status-display:hover {
        cursor: pointer;
    }
</style>

<main class="task">

    <section class="project">
        <h3>Parent Project:</h3>
        <h2><a href='/project.php?pid=<?= $pid ?>'><?= $project['title'] ?></a></h2>
        <h3>(P<?= $project['priority'] ?>)</h3>
        <hr>
        <h2>Links of Parent Project</h2>
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
        <form id="form-new-link" action="" method="POST" style="display: none;">
            <label for="link-description" style="display: block;">Description:</label>
            <input type="text" name="link-description" size="40" required>
            <label for="link-path" style="display: block;">Path:</label>
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

    <section class="details">
        <h2><?= $task['description'] ?></h2>
        <h3><span class='due-date-display'>Due date: <?= $task['due'] ? $task['due'] : "None" ?></span></h3>
        <div id="due-date-div" hidden>
            <form action="" method="POST" style="display: inline-block;">
                <input type='hidden' name='tid' value=<?= $tid ?> />
                <label for='due-date'>Enter due date:</label>
                <input type='date' id='due-date' name='due-date' <?= $task['due'] ? "value='{$task['due']}'" : "" ?>/>
                <button type='submit'>Save</button>
            </form>
            <form action="" method="POST" style="display: inline-block;">
                <input type='hidden' name='tid' value=<?= $tid ?> />
                <input type='hidden' name='clear-due-date' value='true' />
                <button type='submit'>Clear</button>
            </form>
        </div>
        <script>
            const due_date = document.querySelector(".due-date-display");
            const due_date_form = document.querySelector("#due-date-div");
            due_date.addEventListener("click", function(e){
                if (due_date_form.hidden) {
                    due_date_form.hidden = false;
                } else {
                    due_date_form.hidden = true;
                }
            });
        </script>
        <h2>
            <?php if ($task['next']): ?>
                <span style='color: firebrick;'>NEXT</span>
            <?php else: ?>
                <form  action='' method='POST' style='display: inline;'>
                <input type='hidden' name='nextify' value='true' />
                <button type='submit'>nextify</button>
                </form>
            <?php endif; ?>
            &nbsp|&nbsp
            <span id="status-display"><?= $task['status'] ?></span>
            &nbsp|&nbsp
            <?php if (checkQueued($pdo, "task", $tid)): ?>
                QUEUED
            <?php else: ?>
                <form action='' method='POST' style='display: inline;'>
                <input type='hidden' name='tid-for-queue' value='<?= $tid ?>'>
                <button type='submit'>queue</button>
                </form>
            <?php endif; ?>
        </h2>
        <form id="form-status-update" action="" method="POST" hidden>
            <label for="status" style="display: block;">Select a status:</label>
            <select name="status" id="status" required>
                <option value="NOT STARTED">NOT STARTED</option>
                <option value="IN PROGRESS">IN PROGRESS</option>
                <option value="ON HOLD">ON HOLD</option>
                <option value="ABANDONED">ABANDONED</option>
                <option value="COMPLETE">COMPLETE</option>
            </select>
            <br><br>
            <label for="status-note" style="display: block;">Note:</label>
            <textarea name="status-note" rows="6" cols="50" required></textarea>
            <br>
            <button type="submit">Save</button>
        </form>
        <script>
            const status_display = document.querySelector("#status-display");
            const form_status = document.querySelector("#form-status-update");
            status_display.addEventListener("click", function() {
                if (form_status.hidden) {
                    form_status.hidden = false;
                } else {
                    form_status.hidden = true;
                }
            });
        </script>
        <hr>
        <br>
        <?php if ($move_task): ?>
            <?php $projects = getProjects($pdo, "default"); ?>
            <form action="/task.php?tid=<?= $tid ?>" method="POST">
                <input type='hidden' name='tid' value='<?= $tid ?>' />
                <?php foreach ($projects as $prj): ?>
                    <div>
                        <input type='radio' id='<?= $prj['project_id'] ?>' name='move-to-pid' value=<?= $prj['project_id'] ?> required>
                        <label style='display: inline;' for='<?= $prj['project_id'] ?>'><?= "[P{$prj['priority']}] {$prj['title']}" ?></label>
                    </div>
                <?php endforeach; ?>
                <br>
                <button type="submit">Submit</button>
            </form>
            <p><a href="/task.php?tid=<?= $tid ?>">Cancel</a></p>
        <?php else: ?>
        <button id="edit" type="button">Edit description</button>
        <form action="" method="GET" style="display: inline-block;">
            <input type='hidden' name='tid' value='<?= $tid ?>' />
            <input type='hidden' name='move-task' value='true' />
            <button type="submit">Move task</button>
        </form>
        <br>
        <br>
        <form id="form-redescribe" action="" method="POST" hidden>
            <label for="description" style="display: block;">Description:</label>
            <input type="text" name="description" size="60" value="<?= $task['description'] ?>" required>
            <br>
            <button type="submit">Save</button>
            <br>
            <br>
        </form>
        <script>
            const form_redescribe = document.querySelector("#form-redescribe");
            document.querySelector("#edit").addEventListener("click", function() {
                if (form_redescribe.hidden) {
                    form_redescribe.hidden = false;
                } else {
                    form_redescribe.hidden = true;
                }
            });
        </script>
        <?php endif; ?>
    </section>

    <section class="notes">
        <h2>Notes</h2>
        <button type="button" id="btn-new-note">New Note</button>
        <br><br>
        <form id="form-new-note" action="" method="POST" hidden>
            <textarea name="note" rows="6" cols="50" required></textarea>
            <br>
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

<?php include "footer.php" ?>

