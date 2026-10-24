<section class="notes <?= isset($tid) ? 'task-notes' : 'project-notes' ?>">

    <h2>Notes</h2>
    <button type="button" id="btn-new-note">New Note</button>
    <br>
    <br>
    <form id="form-new-note" action="/?action=add-note" method="POST" hidden>
        <?php if (isset($tid)): ?>
            <input type="hidden" name="tid" value=<?= $tid ?> />
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
        $content = preg_replace_callback(
            "/(https:\/\/.*)/",
            function ($matches) {
                if (filter_var($matches[0], FILTER_VALIDATE_URL)) {
                    return "<a href='$matches[0]'>$matches[0]</a>";
                }
                return $matches[0];
            },
            $content);
        $note_id = $note['note_id'];
        $history_url = "./?action=note-history&note-num={$note['note_num']}";
        // leading space important for spacing
        $superceded_tag = " (<a class='deactivated' href='$history_url'>superceded</a>)";
        // trailling space important for spacing
        $edit_tag = "<a href='$history_url'>edit</a> ";
        ?>
        <?php if($note['active']): ?>
            <div class="note-header">
                <div class="header-stamp"><h3 id="header-<?= $note_id ?>"><?= $time ?></h3><pre><?= $note['latest'] ? "" : $superceded_tag ?></pre></div>
                <form action="/?action=deactivate-note" method="POST">
                    <?php if (isset($tid)): ?>
                        <input type="hidden" name="tid" value=<?= $tid ?> />
                    <?php else: ?>
                        <input type="hidden" name="pid" value=<?= $pid ?> />
                    <?php endif; ?>
                    <input type="hidden" name="note-id" value=<?= $note_id ?> />
                    <p id="note-editors-<?= $note_id ?>" class="hidden"><?= $note['latest'] ? $edit_tag : "" ?><span class="editor-link" onclick="this.closest('form').submit()" >deactivate</span></p>
                </form>
            </div>
            <pre><?= $content ?></pre>
        <?php else: ?>
            <div class="note-header deactivated">
                <div class="header-stamp"><h3 id="header-<?= $note_id ?>"><?= $time ?></h3><pre><?= $note['latest'] ? "" : $superceded_tag ?></pre></div>
                <form action="/?action=reactivate-note" method="POST">
                    <?php if (isset($tid)): ?>
                        <input type="hidden" name="tid" value=<?= $tid ?> />
                    <?php else: ?>
                        <input type="hidden" name="pid" value=<?= $pid ?> />
                    <?php endif; ?>
                    <input type="hidden" name="note-id" value=<?= $note_id ?> />
                    <p id="note-editors-<?= $note_id ?>" class="hidden"><span class="editor-link" onclick="this.closest('form').submit()"
                        >reactivate</span></p>
                </form>
            </div>
            <pre id="content-<?= $note_id ?>" class="deactivated hidden"><?= $content ?></pre>
        <?php endif; ?>
        <script>
            document.querySelector("#header-<?= $note_id ?>")
                .addEventListener("click", function toggleEditorsView() {
                    if (document.querySelector("#note-editors-<?= $note_id ?>").classList.contains("hidden")) {
                        document.querySelector("#note-editors-<?= $note_id ?>").classList.remove("hidden");
                        <?php if (!$note['active']): ?>
                            document.querySelector("#content-<?= $note_id ?>").classList.remove("hidden");
                        <?php endif; ?>
                    } else {
                        document.querySelector("#note-editors-<?= $note_id ?>").classList.add("hidden");
                        <?php if (!$note['active']): ?>
                            document.querySelector("#content-<?= $note_id ?>").classList.add("hidden");
                        <?php endif; ?>
                    }
                });
        </script>
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

