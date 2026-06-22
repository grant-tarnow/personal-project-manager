<section class="notes <?= $tid ? 'task-notes' : 'project-notes' ?>">

    <h2>Notes</h2>
    <button type="button" id="btn-new-note">New Note</button>
    <br>
    <br>
    <form id="form-new-note" action="/?action=add-note" method="POST" hidden>
        <?php if ($tid): ?>
            <input type="hidden" name="tid" value="<?= $tid ?>" />
        <?php else: ?>
            <input type="hidden" name="pid" value=<?= $pid ?> />
        <?php endif; ?>
        <textarea name="note" rows="6" required></textarea>
        <button type="submit">Save</button>
    </form>

    <?php foreach ($notes as $note): ?>
        <?php
        $time = dtLocal($note['created']);
        $content = preg_replace("/(pid:(\d+))/", "<a href='/?action=show-project&pid=$2'>$1</a>", $note['content']);
        $content = preg_replace("/(tid:(\d+))/", "<a href='/?action=show-task&tid=$2'>$1</a>", $content);
        ?>
        <h3><?= $time ?></h3>
        <pre><?= $content ?></pre>
    <?php endforeach; ?>

    <script>
        const btn_note = document.querySelector("#btn-new-note");
        const form_note = document.querySelector("#form-new-note");
        btn_note.addEventListener("click", function() {
            if (form_note.hidden) {
                form_note.hidden = false;
            } else {
                form_note.hidden = true;
            }
        });
    </script>

</section>

