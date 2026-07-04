<table>
    <thead><tr>
        <th>Type</th>
        <th>Due</th>
        <th>Status</th>
        <th>Title/Description</th>
        <th>Position</th>
    </tr></thead>
    <tbody>
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
            $prj = getProject($item['project_id']);
            $status = $prj['status'];
            $due = $prj['due'];
            $tod = $prj['title'];
            $url = "/?action=show-project&pid={$item['project_id']}";
            $color = statusColor($prj['status']);
        } else if ($item['task_id']) {
            $type = "Task";
            $task = getTask($item['task_id']);
            $status = $task['status'];
            $due = $task['due'];
            $tod = $task['description'];
            $url = "/?action=show-task&tid={$item['task_id']}";
            $color = statusColor($task['status']);
        }
        $due_color = setDueColor($due);
        ?>
        <tr id="<?= "queue{$item['position']}" ?>" <?= $due_color ?> >
            <td><?= $type ?></td>
            <td class="due"><?= $due ?></td>
            <td style="color: <?= $color ?>"><?= $status ?></td>
            <td><?= $tod ?></td>
            <td class="no-click pos">
                <form action="" method="POST" class="just-btn" >
                    <input type="hidden" name="current-pos" value="<?= $item['position'] ?>">
                    <select id="pos-selector" name="selected-pos" onchange="this.form.submit()">
                        <?php for ($i = 1; $i <= count($queue); $i++): ?>
                            <option value=<?= $i ?> <?= $item['position'] == $i ? "selected" : "" ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
                <form action="" method="POST" class="just-btn">
                    <input type="hidden" name="pos-rm" value="<?= $item['position'] ?>">
                    <button type="submit" >rm</button>
                </form>
            </td>
        </tr>
        <script>
            document.querySelector("<?= "#queue{$item['position']}"; ?>").addEventListener("click", function(e) {
                if (!e.target.classList.contains("no-click") && e.target.tagName != "SELECT") {
                    window.location = "<?= $url; ?>";
                }
            });
        </script>
    <?php endforeach; ?>
    </tbody>
</table>
