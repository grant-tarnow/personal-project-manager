<table>
    <thead>
    <tr>
        <th>Due</th>
        <th>Status</th>
        <th>Description</th>
        <th>Parent Project</th>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($tasks as $task): ?>
        <?php
            $task_color = statusColor($task['status']);
            $prj = getProject($task['project_id']);
            $prj_title = $prj['title'];
        ?>
        <tr id="<?= "task{$task['task_id']}" ?>">
            <td class="due"><?= $task['due'] ?></td>
            <td style="color: <?= $task_color ?>"><?= $task['status'] ?></td>
            <td><?= $task['description'] ?></td>
            <td><?= $prj_title ?></td>
        </tr>
        <script>
            document.querySelector("<?= "#task{$task['task_id']}" ?>").addEventListener("click", function() {
                window.location = "<?= "/?action=show-task&tid={$task['task_id']}" ?>";
            });
        </script>
    <?php endforeach; ?>
    </tbody>

</table>
