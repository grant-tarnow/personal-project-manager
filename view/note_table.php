<table>
    <thead>
    <tr>
        <th>Parent Type</th>
        <th>Title/Description</th>
        <th>Timestamp</th>
        <th>Content</th>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($notes as $note): ?>
        <tr id="<?= "note{$note['note_id']}" ?>">
            <td><?= $note['pot'] ?></td>
            <td><?= $note['tod'] ?></td>
            <td class="due"><?= $note['created'] ?></td>
            <td><?= $note['content'] ?></td>
        </tr>
        <script>
            document.querySelector("<?= "#note{$note['note_id']}" ?>").addEventListener("click", function() {
                window.location = "<?= $note['parent_url'] ?>";
            });
        </script>
    <?php endforeach; ?>
    </tbody>

</table>
