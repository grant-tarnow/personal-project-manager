<?php
$task_notes = getNotesOfTask($task['task_id']);
$task_updates = getUpdatesOfTask($task['task_id']);
$color = statusColor($task['status']);
?>

<style>

.task-card {
    padding: 20px;
    display: grid;
    grid-template-columns: 6fr 1fr;
    grid-template-areas:
        "task-card-header task-card-header"
        "task-card-left task-card-right";
} .task-card:hover {
    background: skyblue;
    cursor: pointer;
}

.task-card-header {
    grid-area: task-card-header;
}

.task-card-left {
    grid-area: task-card-left;
}

.task-card-right {
    grid-area: task-card-right;
}

</style>

<div class="task-card" id='<?= "task{$task['task_id']}" ?>'>
    <div class="task-card-header">
        <h3><?= $task['description'] ?></h3>
    </div>
    <div class="task-card-left">
        <h3>
        <?php if ($task['next']): ?>
            <span style='color: firebrick;'>NEXT</span>
        <?php else: ?>
            <form  action='/?action=nextify-from-project' method='POST' style='display: inline;'>
            <input type='hidden' name='tid' value='<?= $task['task_id'] ?>' />
            <input type='hidden' name='pid' value='<?= $task['project_id'] ?>' />
            <button type='submit' class="solo-btn">nextify</button>
            </form>
        <?php endif; ?>
        |
        <span style="color: <?= $color ?>"><?= $task['status'] ?></span>
        |
        <?php if (checkQueued("task", $task['task_id'])): ?>
        QUEUED
        <?php else: ?>
            <form action='/?action=queue-task-from-project' method='POST' style='display: inline;'>
            <input type='hidden' name='tid' value='<?= $task['task_id'] ?>' />
            <input type='hidden' name='pid' value='<?= $task['project_id'] ?>' />
            <button type='submit' class="solo-btn">queue</button>
            </form>
        <?php endif; ?>
        </h3>
        <table>
            <tr>
                <td>due:</td>
                <td><?= $task['due'] ?></td>
            </tr>
            <tr>
                <td>notes:</td>
                <td><?= count($task_notes) ?></td>
            </tr>
            <tr>
                <td>last update:</td>
                <td><?= $task_updates[0]['created'] ?? $task['updated'] ?></td>
            </tr>
            <tr>
                <td>created:</td>
                <td><?= $task['created'] ?></td>
            </tr>
        </table>
    </div>
    <div class="task-card-right">
        <h4>MOVE</h4>
        <form action='/?action=move-task-up' method='POST'>
            <input type='hidden' name='tid' value='<?= $task['task_id'] ?>' />
            <input type='hidden' name='pid' value='<?= $task['project_id'] ?>' />
            <button type='submit' class="solo-btn">up</button>
        </form>
        <br>
        <form action='/?action=move-task-down' method='POST'>
            <input type='hidden' name='tid' value='<?= $task['task_id'] ?>' />
            <input type='hidden' name='pid' value='<?= $task['project_id'] ?>' />
        <button type='submit' class="solo-btn">dn</button>
        </form>
    </div>
</div>
<script>
    document.querySelector('<?= "#task{$task['task_id']}" ?>').addEventListener("click", function() {
        window.location = '/?action=show-task&tid=<?= $task['task_id'] ?>';
    });
</script>
