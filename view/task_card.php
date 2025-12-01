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
                    <td><?= dtEastern($task_updates[0]['created'] ?? $task['updated']) ?></td>
                </tr>
                <tr>
                    <td>created:</td>
                    <td><?= dtEastern($task['created']) ?></td>
                </tr>
            </table>
        </div>
        <div class="task-card-controls">
            <?php if ($task['position']): ?>
                <h4>ORDER</h4>
                <form class="just-btn" action="/?action=update-task-position" method="POST">
                    <input type="hidden" name="tid" value="<?= $task['task_id'] ?>" />
                    <input type="hidden" name="pid" value="<?= $task['project_id'] ?>" />
                    <input type="hidden" name="current-pos" value="<?= $task['position'] ?>" />
                    <select id="pos-selector" name="selected-pos" onchange="this.form.submit()">
                        <?php for ($i = 1; $i <= count($incomplete_tasks); $i++): ?>
                            <option value=<?= $i ?> <?= $task['position'] == $i ? "selected" : "" ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    document.querySelector("<?= "#task{$task['task_id']}" ?>").addEventListener("click", function(e) {
        if (e.target.tagName != "SELECT") {
            window.location = "/?action=show-task&tid=<?= $task['task_id'] ?>";
        }
    });
</script>
