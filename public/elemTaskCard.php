<?php
$task_notes = getNotesOfTask($pdo, $task['task_id']);
$task_updates = getUpdatesOfTask($pdo, $task['task_id']);
$color = statusColor($task['status']);
?>

<div class="task-card" id='<?php echo "task{$task['task_id']}"; ?>'>
    <?php
    echo "<h3>{$task['description']}</h3>";
    echo "<h3>";
    if ($task['next']) {
        echo "<span style='color: firebrick;'>NEXT</span>";
    } else {
        echo <<<END
        <form  action='' method='POST' style='display: inline;'>
        <input type='hidden' name='nextify-tid' value='{$task['task_id']}' />
        <button type='submit'>nextify</button>
        </form>
        END;
    }
    echo " | <span style='color: $color;'>{$task['status']}</span> | ";
    if (checkQueued($pdo, "task", $task['task_id'])) {
        echo "QUEUED";
    } else {
        echo <<<END
        <form action='' method='POST' style='display: inline;'>
        <input type='hidden' name='tid-for-queue' value='{$task['task_id']}'>
        <button type='submit'>queue</button>
        </form>
        END;
    }
    echo "</h3>";
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
<script>
    document.querySelector('<?php echo "#task{$task['task_id']}"; ?>').addEventListener("click", function() {
        window.location = '/task.php?tid=<?php echo $task['task_id']; ?>';
    });
</script>
