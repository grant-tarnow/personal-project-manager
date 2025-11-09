<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";
require_once "../lib/utility.php";

$pdo = dbConnect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pos_up = filter_input(INPUT_POST, "pos-up", FILTER_VALIDATE_INT);
    $pos_dn = filter_input(INPUT_POST, "pos-dn", FILTER_VALIDATE_INT);
    $pos_rm = filter_input(INPUT_POST, "pos-rm", FILTER_VALIDATE_INT);

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

$queue = getQueue($pdo);
$projects = getProjectsByDue($pdo, 2);
$tasks = getTasksByDue($pdo, 2);
$date_queue = array_merge($projects, $tasks);

function sort_by_due($a, $b) {
    if ($a['due'] == $b['due']) {
        return 0;
    }
    return ($a['due'] > $b['due'] ? 1 : -1);
}

usort($date_queue, "sort_by_due");

?>

<?php include "header.php" ?>

<style>
    .queues {
        display: flex;
        flex-flow: row nowrap;
        justify-content: space-between;
        gap: 30px;
        width: 100%;
    }
    .date-queue {
        flex: 1;
    }
    .my-queue {
        flex: 1;
    }
</style>

<main class="queues">

    <section class="date-queue">
        <h2>Date Queue</h2>
        <table>
            <tr>
                <th>Type</th>
                <th>Due</th>
                <th>Status</th>
                <th>Title/Description</th>
            </tr>
            <?php foreach ($date_queue as $item): ?>
                <?php
                $type = "";
                $id = "";
                $status = "";
                $due = "";
                $tod = "";
                $url = "";
                $color = "";
                if (array_key_exists('task_id', $item)) {
                    $type = "Task";
                    $id = "prj{$item['task_id']}";
                    $status = $item['status'];
                    $due = $item['due'];
                    $tod = $item['description'];
                    $url = "/task.php?tid={$item['task_id']}";
                    $color = statusColor($item['status']);
                } else {
                    $type = "Project";
                    $id = "prj{$item['project_id']}";
                    $status = $item['status'];
                    $due = $item['due'];
                    $tod = $item['title'];
                    $url = "/project.php?pid={$item['project_id']}";
                    $color = statusColor($item['status']);
                }
                ?>
                <tr id="<?= $id ?>">
                    <td><?= $type ?></td>
                    <td><?= $due ?></td>
                    <td style='color: <?= $color ?>;'><?= $status ?></td>
                    <td><?= $tod ?></td>
                </tr>
                <script>
                    document.querySelector("#<?= $id ?>").addEventListener("click", function() {
                        window.location = "<?= $url ?>";
                    });
                </script>
            <?php endforeach; ?>
        </table>
    </section>

    <section class="my-queue">

        <h2>My Queue</h2>

        <table>
            <tr>
                <th>Type</th>
                <th>Due</th>
                <th>Status</th>
                <th>Title/Description</th>
                <th>Manage</th>
            </tr>

        <?php foreach ($queue as $item): ?>
            <?php
            $type = "";
            $status = "";
            $due = "";
            $tod = "";
            $url = "";
            $color = "";
            if ($item['project_id']) {
                $type = "Project";
                $prj = getProject($pdo, $item['project_id']);
                $status = $prj['status'];
                $due = $prj['due'];
                $tod = $prj['title'];
                $url = "/project.php?pid={$item['project_id']}";
                $color = statusColor($prj['status']);
            } else if ($item['task_id']) {
                $type = "Task";
                $task = getTask($pdo, $item['task_id']);
                $status = $task['status'];
                $due = $task['due'];
                $tod = $task['description'];
                $url = "/task.php?tid={$item['task_id']}";
                $color = statusColor($task['status']);
            }
            ?>
            <tr id='<?= "queue{$item['position']}" ?>'>
                <td><?= $type ?></td>
                <td><?= $due ?></td>
                <td style='color: <?= $color ?>'><?= $status ?></td>
                <td><?= $tod ?></td>
                <td>
                    <form action='' method='POST' style='display: inline;'>
                    <input type='hidden' name='pos-up' value='<?= $item['position'] ?>'>
                    <button type='submit'>up</button>
                    </form>
                    <form action='' method='POST' style='display: inline;'>
                    <input type='hidden' name='pos-rm' value='<?= $item['position'] ?>'>
                    <button type='submit'>rm</button>
                    </form>
                    <form action='' method='POST' style='display: inline;'>
                    <input type='hidden' name='pos-dn' value='<?= $item['position'] ?>'>
                    <button type='submit'>dn</button>
                    </form>
                </td>
            </tr>
            <script>
                document.querySelector("<?= "#queue{$item['position']}"; ?>").addEventListener("click", function() {
                    window.location = "<?= $url; ?>";
                });
            </script>
        <?php endforeach; ?>
        </table>
    </section>

</main>

<?php include "footer.php" ?>

