<?php include "../view/header.php" ?>

<h3>Parent <?= $type ?>: <a href="<?= $url ?>"><?= $name ?></a></h3>

<table class="history-table">
    <thead>
        <tr>
            <th class="version" >VERSION</th>
            <th class="timestamp" >TIMESTAMP</th>
            <th>CONTENT</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="no-click"><button type="button" id="btn-edit-note">New</button><span id="indicator" class="hidden"><?= $latest['version'] + 1 ?></span></td>
            <td></td>
            <td class="no-click">
                <form id="note-editor" class="hidden" action="/?action=update-note" method="POST">
                    <input type="hidden" name="note-num" value=<?= $latest['note_num'] ?> />
                    <textarea name="content" rows="12" required><?= $latest['content'] ?></textarea>
                    <button type="submit">Save</button>
                </form>
            </td>
        </tr>
        <?php foreach ($history as $v): ?>
            <?php
                $time = dtLocal($v['created']);
            ?>
            <tr>
                <td class="no-click"><?= $v['version']?></td>
                <td class="no-click"><?= $time ?></td>
                <td class="no-click"><pre><?= $v['content'] ?></pre></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    const editor = document.querySelector("#note-editor");
    const indicator = document.querySelector("#indicator");
    document.querySelector("#btn-edit-note").addEventListener("click", function showNoteEditor() {
        editor.classList.remove("hidden");
        indicator.classList.remove("hidden");
        this.classList.add("hidden");
    });
</script>

<?php include "../view/footer.php" ?>

