<?php
$task_notes = getNotesOfTask($task['task_id']);
$task_updates = getUpdatesOfTask($task['task_id']);
$color = statusColor($task['status']);
?>

<div class="task-card" id="<?= "task{$task['task_id']}" ?>">
    <h3><?= $task['description'] ?></h3>
    <div class="task-card-body">
        <div class="task-card-main">
            <h3>
            <?php if ($task['next']): ?>
                <span style="color: firebrick;">NEXT</span>
            <?php else: ?>
                <form class="just-btn" action="/?action=nextify-from-project" method="POST" >
                    <input type="hidden" name="tid" value="<?= $task['task_id'] ?>" />
                    <input type="hidden" name="pid" value="<?= $task['project_id'] ?>" />
                    <button type="submit" >nextify</button>
                </form>
            <?php endif; ?>
            |
            <span style="color: <?= $color ?>"><?= $task['status'] ?></span>
            |
            <?php if (checkQueued("task", $task['task_id'])): ?>
            QUEUED
            <?php else: ?>
                <form class="just-btn" action="/?action=queue-task-from-project" method="POST" >
                    <input type="hidden" name="tid" value="<?= $task['task_id'] ?>" />
                    <input type="hidden" name="pid" value="<?= $task['project_id'] ?>" />
                    <button type="submit" >queue</button>
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
        <div class="task-card-controls">
            <h4>MOVE</h4>
            <form class="just-btn" action="/?action=move-task-up" method="POST">
                <input type="hidden" name="tid" value="<?= $task['task_id'] ?>" />
                <input type="hidden" name="pid" value="<?= $task['project_id'] ?>" />
                <button type="submit" >up</button>
            </form>
            <br>
            <form class="just-btn" action="/?action=move-task-down" method="POST">
                <input type="hidden" name="tid" value="<?= $task['task_id'] ?>" />
                <input type="hidden" name="pid" value="<?= $task['project_id'] ?>" />
            <button type="submit" >dn</button>
            </form>
        </div>
    </div>
</div>
<script>
    document.querySelector("<?= "#task{$task['task_id']}" ?>").addEventListener("click", function() {
        window.location = "/?action=show-task&tid=<?= $task['task_id'] ?>";
    });
</script>
