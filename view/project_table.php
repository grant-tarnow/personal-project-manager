<table>
    <thead>
    <tr>
        <th>Pri</th>
        <th>Due</th>
        <th>Status</th>
        <th>Title</th>
        <th>Next</th>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($projects as $prj): ?>
        <?php
        $prj_color = statusColor($prj['status']);
        $next = getOpenTasksOfProject($prj['project_id'])[0] ?? NULL;
        if (!$next) {
            $next = ['description' => 'None'];
        }
        ?>
        <tr id="<?= "prj{$prj['project_id']}" ?>">
            <td><?= $prj['priority'] ?></td>
            <td class="due"><?= $prj['due'] ?></td>
            <td style="color: <?= $prj_color ?>"><?= $prj['status'] ?></td>
            <td><?= $prj['title'] ?></td>
            <td><?= $next['description'] ?></td>
        </tr>
        <script>
            document.querySelector("<?= "#prj{$prj['project_id']}" ?>").addEventListener("click", function() {
                window.location = "<?= "/?action=show-project&pid={$prj['project_id']}" ?>";
            });
        </script>
    <?php endforeach; ?>
    </tbody>

</table>
