<?php
$task_notes = getNotesOfTask($pdo, $task['task_id']);
$task_updates = getUpdatesOfTask($pdo, $task['task_id']);
$color = statusColor($task['status']);
?>

<style>

.task-card {
    padding: 20px;
    display: grid;
    grid-template-columns: 5fr 1fr;
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
            <form  action='' method='POST' style='display: inline;'>
            <input type='hidden' name='nextify-tid' value='<?= $task['task_id'] ?>' />
            <button type='submit'>nextify</button>
            </form>
        <?php endif; ?>
        &nbsp|&nbsp
        <span style="color: <?= $color ?>"><?= $task['status'] ?></span>
        &nbsp|&nbsp
        <?php if (checkQueued($pdo, "task", $task['task_id'])): ?>
        QUEUED
        <?php else: ?>
            <form action='' method='POST' style='display: inline;'>
            <input type='hidden' name='tid-for-queue' value='<?= $task['task_id'] ?>'>
            <button type='submit'>queue</button>
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
        <form action='' method='POST'>
        <input type='hidden' name='task-pos-up' value='<?= $task['task_id'] ?>'>
        <button type='submit'>up</button>
        </form>
        <br>
        <form action='' method='POST'>
        <input type='hidden' name='task-pos-dn' value='<?= $task['task_id'] ?>'>
        <button type='submit'>dn</button>
        </form>
    </div>
</div>
<script>
    document.querySelector('<?= "#task{$task['task_id']}" ?>').addEventListener("click", function() {
        window.location = '/task.php?tid=<?= $task['task_id'] ?>';
    });
</script>
