<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";
require_once "../lib/utility.php";

$pdo = dbConnect();

$view = $_GET['view'] ?? "default";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);
    $pos_up = filter_input(INPUT_POST, "pos-up", FILTER_VALIDATE_INT);
    $pos_dn = filter_input(INPUT_POST, "pos-dn", FILTER_VALIDATE_INT);
    $pos_rm = filter_input(INPUT_POST, "pos-rm", FILTER_VALIDATE_INT);

    if ($title) { // not checking $priority because 0 is falsey
        addProject($pdo, $title, $priority);
    }
    if ($pos_up) {
        moveUp($pdo, $pos_up);
    }
    if ($pos_dn) {
        moveDown($pdo, $pos_dn);
    }
    if ($pos_rm) {
        removeFromQueueByPosition($pdo, $pos_rm);
    }

}

$projects = getProjects($pdo, $view);
$queue = getQueue($pdo);

?>

<?php include "header.php" ?>

<section class="left">

    <h2>Project Views</h2>
    <p><a href="/?view=default">Default</a></p>
    <p><a href="/?view=active">Active</a></p>
    <p><a href="/?view=hold">On Hold</a></p>
    <p><a href="/?view=incomplete">Incomplete</a></p>
    <p><a href="/?view=complete">Complete</a></p>
    <p><a href="/?view=all">All</a></p>

</section>

<section class="center">

    <h2>Queue</h2>

    <table>
        <tr>
            <th>Type</th>
            <th>Status</th>
            <th>Title/Description</th>
            <th>Manage</th>
        </tr>

    <?php foreach ($queue as $item): ?>
        <?php
        $type = "";
        $status = "";
        $tod = "";
        $url = "";
        if ($item['project_id']) {
            $type = "Project";
            $prj = getProject($pdo, $item['project_id']);
            $status = $prj['status'];
            $tod = $prj['title'];
            $url = "/project.php?pid={$item['project_id']}";
        } else if ($item['task_id']) {
            $type = "Task";
            $task = getTask($pdo, $item['task_id']);
            $status = $task['status'];
            $tod = $task['description'];
            $url = "/task.php?tid={$item['task_id']}";
        }
        ?>
        <tr id='<?php echo "queue{$item['position']}"; ?>'>
            <?php
            echo <<<END
            <td>$type</td>
            <td>$status</td>
            <td>$tod</td>
            <td>
                <form action='' method='POST' style='display: inline;'>
                <input type='hidden' name='pos-up' value='{$item['position']}'>
                <button type='submit'>up</button>
                </form>
                <form action='' method='POST' style='display: inline;'>
                <input type='hidden' name='pos-rm' value='{$item['position']}'>
                <button type='submit'>rm</button>
                </form>
                <form action='' method='POST' style='display: inline;'>
                <input type='hidden' name='pos-dn' value='{$item['position']}'>
                <button type='submit'>dn</button>
                </form>
            </td>
            END;
            ?>
        </tr>
        <script>
            document.querySelector("<?php echo "#queue{$item['position']}"; ?>").addEventListener("click", function() {
                window.location = "<?php echo $url; ?>";
            });
        </script>

    <?php endforeach; ?>

    </table>
    

</section>

<section class='right'>

    <h2>Projects List</h2>

    <table>
        <tr>
            <th>Priority</th>
            <th>Status</th>
            <th>Title</th>
        </tr>

    <?php foreach ($projects as $prj): ?>
        <tr id='<?php echo "prj{$prj['project_id']}"; ?>'>
            <?php
            $prj_color = statusColor($prj['status']);
            echo "<td>{$prj['priority']}</td>";
            echo "<td style='color: $prj_color;'>{$prj['status']}</td>";
            echo "<td>{$prj['title']}</td>";
            ?>
        </tr>
        <script>
            document.querySelector("<?php echo "#prj{$prj['project_id']}"; ?>").addEventListener("click", function() {
                window.location = "<?php echo "/project.php?pid={$prj['project_id']}"; ?>";
            });
        </script>
    <?php endforeach; ?>

    </table>

    <br>

    <button type="button" id="btn-add-project">New Project</button>
    <br><br>
    <form id="form-add-project" action="" method="POST" style="display: none;">
        <label for="title">Title:</label>
        <input type="text" name="title" required>
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
        <br>
        <button type="submit">Save</button>
    </form>
    <script>
        const btn_prj = document.querySelector("#btn-add-project");
        const form_prj = document.querySelector("#form-add-project");
        btn_prj.addEventListener("click", function() {
            if (form_prj.style.display == "none") {
                form_prj.style.display = "block";
            } else {
                form_prj.style.display = "none";
            }
        });
    </script>

</section

<?php include "footer.php" ?>

