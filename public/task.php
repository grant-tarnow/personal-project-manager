<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";
require_once "../lib/utility.php";

$pdo = dbConnect();

$tid = $_GET['tid'] ?? $_POST['tid'];
$pid = getTask($pdo, $tid)['project_id'];
$project = getProject($pdo, $pid);

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

}

$task = getTask($pdo, $tid); // need to fetch this after updates to render correctly after a POST
$notes = array_reverse(getNotesOfTask($pdo, $tid));
$links = getLinksOfProject($pdo, $pid);

?>

<?php include "header.php" ?>

<section class="left">

    <?php
    echo "<h3>Task of Project: <a href='/project.php?pid=$pid'>{$project['title']} (P{$project['priority']})</a></h3>";
    $task_color = statusColor($task['status']);
    echo "<h2>{$task['description']}</h2>";
    ?>

    <p id='edit'>edit</p>
    <form id="form-redescribe" action="" method="POST" style="display: none;">
        <label for="description">Description:</label>
        <input type="text" name="description" value="<?php echo "{$task['description']}" ?>" required>
        <br>
        <button type="submit">Save</button>
        <br><br>
    </form>
    <script>
        const form_redescribe = document.querySelector("#form-redescribe");
        document.querySelector("#edit").addEventListener("click", function() {
            if (form_redescribe.style.display == "none") {
                form_redescribe.style.display = "block";
            } else {
                form_redescribe.style.display = "none";
            }
        });
    </script>

    <?php
    if ($task['next']) {
        echo "<h3 style='color: firebrick;'>NEXT</h3>";
    } else {
        echo "<form  action='' method='POST'>";
        echo "<input type='hidden' name='nextify' value='true' />";
        echo "<button type='submit'>nextify</button>";
        echo "</form>";
    }
    echo "<h2><span style='color: $task_color;'>{$task['status']}</span> | ";
    if (checkQueued($pdo, "task", $tid)) {
        echo "QUEUED";
    } else {
        echo <<<END
        <form action='' method='POST' style='display: inline;'>
        <input type='hidden' name='tid-for-queue' value='$tid'>
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

    <h2>Links of Parent Project</h2>

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
        <input type="text" name="link-description" required>
        <label for="link-path">Path:</label>
        <input type="text" name="link-path" required>
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

    <h2>Unused</h2>

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
        echo "<h3>{$note['created']}</h3>";
        echo "<pre>{$note['content']}</pre>";
        ?>
    <?php endforeach; ?>

</section>

<?php include "footer.php" ?>

