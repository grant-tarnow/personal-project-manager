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

    if ($note) {
        addNote($pdo, "project", $pid, $note);
    }
    if ($link_descr && $link_path) {
        addLink($pdo, $pid, $link_descr, $link_path);
    }
    if ($task_descr) {
        addTask($pdo, $pid, $task_descr);
    }

}

$project = getProject($pdo, $pid)[0];
$tasks = getTasksOfProject($pdo, $pid);
$notes = array_reverse(getNotesOfProject($pdo, $pid));
$links = getLinksOfProject($pdo, $pid);

?>

<?php include "header.php" ?>

<section class="left">

    <h2>Project</h2>
    <h3><?php echo "{$project['title']}"; ?></h3>
    <p><?php echo "PRIORITY: {$project['priority']}"; ?></p>
    <p><?php echo "STATUS:   {$project['status']}"; ?></p>
    <p><?php echo "NOTES:    " . count($notes); ?></p>
    <p><?php echo "LINKS:    " . count($links); ?></p>
    <p><?php echo "TASKS:    " . count($tasks); ?></p>

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

    <h2>Task List</h2>

    <button type="button" id="btn-new-task">New Task</button>
    <br><br>
    <form id="form-new-task" action="" method="POST" style="display: none;">
        <label for="task-description">Description:</label>
        <input type="text" name="task-description" required>
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

    <?php foreach ($tasks as $task): ?>
        <div class="task-card">
        <?php
        $task_notes = getNotesOfTask($pdo, $task['task_id']);
        $task_updates = getUpdatesOfTask($pdo, $task['task_id']);
        $color = statusColor($task['status']);
        echo "<h3>{$task['description']} | <span style='color: $color;'>{$task['status']}</span></h3>";
        ?>
        <table>
            <tr>
                <td>notes:</td>
                <td><?php echo count($task_notes); ?></td>
            </tr>
            <tr>
                <td>last update:</td>
                <td><?php echo $task_updates[0]['created'] ?? $task['updated']; ?></td>
            </tr>
            <tr>
                <td>created:</td>
                <td><?php echo $task['created']; ?></td>
            </tr>
        </table>
        </div>
    <?php endforeach; ?>


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
