<table>
    <thead>
    <tr>
        <th>Description</th>
        <th>Path</th>
        <th>Parent Project</th>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($links as $link): ?>
        <?php
            $prj = getProject($link['project_id']);
            $prj_title = $prj['title'];
        ?>
        <tr id="<?= "link{$link['link_id']}" ?>">
            <td><?= $link['description'] ?></td>
            <td><?= $link['path'] ?></td>
            <td><?= $prj_title ?></td>
        </tr>
        <script>
            document.querySelector("<?= "#link{$link['link_id']}" ?>").addEventListener("click", function() {
                window.location = "<?= "/?action=show-project&pid={$prj['project_id']}" ?>";
            });
        </script>
    <?php endforeach; ?>
    </tbody>

</table>
