<table>
    <thead><tr>
        <th>Type</th>
        <th>Due</th>
        <th>Status</th>
        <th>Title/Description</th>
        <th>Q</th>
    </tr></thead>
    <tbody>
    <?php foreach ($date_queue as $item): ?>
        <?php
        $type = "";
        $elem_id = "";
        $status = "";
        $due = "";
        $tod = "";
        $url = "";
        $color = "";
        $top = 0;
        if (array_key_exists('task_id', $item)) {
            $top = $item['task_id'];
            $type = "Task";
            $elem_id = "dqtask{$top}";
            $tod = $item['description'];
            $url = "/?action=show-task&tid={$top}";
            $color = statusColor($item['status']);
        } else {
            $top = $item['project_id'];
            $type = "Project";
            $elem_id = "dqprj{$top}";
            $tod = $item['title'];
            $url = "/?action=show-project&pid={$top}";
            $color = statusColor($item['status']);
        }
        $due = $item['due'];
        $status = $item['status'];
        $due_color = setDueColor($due);
        ?>
        <tr id="<?= $elem_id ?>" <?= $due_color ?> >
            <td><?= $type ?></td>
            <td class="due"><?= $due ?></td>
            <td style="color: <?= $color ?>;"><?= $status ?></td>
            <td><?= $tod ?></td>
            <td>
                <?php if (checkQueued(strtolower($type), $top)): ?>
                Q
                <?php else: ?>
                    <form class="just-btn" action="/?action=queue-from-date-queue" method="POST" >
                        <input type="hidden" name="<?= $type == 'Project' ? 'pid' : 'tid'; ?>" value=<?= $top ?>>
                        <button type="submit" >Q</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <script>
            document.querySelector("#<?= $elem_id ?>").addEventListener("click", function() {
                window.location = "<?= $url ?>";
            });
        </script>
    <?php endforeach; ?>
    </tbody>
</table>

