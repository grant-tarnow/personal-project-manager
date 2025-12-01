<?php include "../view/header.php" ?>

<main class="task-view">

    <section class="task-project">
        <h3>Parent Project:</h3>
        <h2><a href="/?action=show-project&pid=<?= $pid ?>"><?= $project['title'] ?></a></h2>
        <h3>(P<?= $project['priority'] ?>)</h3>
        <hr>
        <h2>Links of Parent Project</h2>
        <?php foreach ($links as $link): ?>
            <?php if (filter_var($link['path'], FILTER_VALIDATE_URL)): ?>
                <p><a href="<?= $link['path'] ?>"><?= $link['description'] ?></a></p>
            <?php else: ?>
                <pre><?= "{$link['description']}\n\t{$link['path']}" ?></pre>
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="button" id="btn-new-link">New Link</button>
        <br>
        <br>
        <form id="form-new-link" action="/?action=add-link-from-task" method="POST" hidden>
            <input type="hidden" name="tid" value=<?= $tid ?> />
            <input type="hidden" name="pid" value=<?= $pid ?> />
            <label for="link-description" >Description:</label><br>
            <input type="text" name="link-description" required>
            <label for="link-path" >Path:</label><br>
            <input type="text" name="link-path" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const btn_link = document.querySelector("#btn-new-link");
            const form_link = document.querySelector("#form-new-link");
            btn_link.addEventListener("click", function() {
                if (form_link.hidden) {
                    form_link.hidden = false;
                } else {
                    form_link.hidden = true;
                }
            });
        </script>
    </section>

    <section class="task-details">
        <h2><?= $task['description'] ?></h2>
        <h3><span class="due-date-display">Due date: <?= $task['due'] ? $task['due'] : "None" ?></span></h3>
        <div id="due-date-div" hidden>
            <form action="/?action=update-task-due" method="POST" style="display: inline;">
                <input type="hidden" name="tid" value=<?= $tid ?> />
                <label for="due-date">Enter due date:</label>
                <input type="date" id="due-date" name="due-date" <?= $task['due'] ? "value='{$task['due']}'" : "" ?>/>
                <button type="submit">Save</button>
            </form>
            <form class="just-btn" action="/?action=clear-task-due" method="POST" >
                <input type="hidden" name="tid" value=<?= $tid ?> />
                <button type="submit">Clear</button>
            </form>
        </div>
        <script>
            const due_date = document.querySelector(".due-date-display");
            const due_date_form = document.querySelector("#due-date-div");
            due_date.addEventListener("click", function(e){
                if (due_date_form.hidden) {
                    due_date_form.hidden = false;
                } else {
                    due_date_form.hidden = true;
                }
            });
        </script>
        <h2>
            <span class="status-display" id="task-status" style="color: <?= $status_color ?>;"><?= $task['status'] ?></span>
            |
            <?php if (checkQueued("task", $tid)): ?>
                QUEUED
            <?php else: ?>
                <form class="just-btn" action="/?action=queue-task" method="POST" >
                    <input type="hidden" name="tid" value=<?= $tid ?> />
                    <button type="submit" >queue</button>
                </form>
            <?php endif; ?>
        </h2>
        <form id="form-status-update" action="/?action=update-task-status" method="POST" hidden>
            <input type="hidden" name="tid" value=<?= $tid ?> />
            <label for="status" >Select a status:</label><br>
            <select name="status" id="status" required>
                <option value="NOT STARTED">NOT STARTED</option>
                <option value="IN PROGRESS">IN PROGRESS</option>
                <option value="ON HOLD">ON HOLD</option>
                <option value="ABANDONED">ABANDONED</option>
                <option value="COMPLETE">COMPLETE</option>
            </select>
            <br><br>
            <label for="status-note" >Note:</label><br>
            <textarea id="status-note" name="note" rows="6" required></textarea>
            <button type="submit">Save</button>
        </form>
        <script>
            const status_display = document.querySelector("#task-status");
            const form_status = document.querySelector("#form-status-update");
            status_display.addEventListener("click", function() {
                if (form_status.hidden) {
                    form_status.hidden = false;
                } else {
                    form_status.hidden = true;
                }
            });
        </script>
        <hr>
        <?php if ($move_task): ?>
            <?php $projects = getProjects("default"); ?>
            <form action="/?action=move-task" method="POST">
                <input type="hidden" name="tid" value="<?= $tid ?>" />
                <?php foreach ($projects as $prj): ?>
                    <div>
                        <input type="radio" id="<?= $prj['project_id'] ?>" name="pid" value=<?= $prj['project_id'] ?> required>
                        <label for="<?= $prj['project_id'] ?>"><?= "[P{$prj['priority']}] {$prj['title']}" ?></label>
                    </div>
                <?php endforeach; ?>
                <button type="submit">Submit</button>
            </form>
            <p><a href="/?action=show-task&tid=<?= $tid ?>">Cancel</a></p>
        <?php else: ?>
        <button id="edit" type="button">Edit description</button>
        <form class="just-btn" action="." method="GET" >
            <input type="hidden" name="action" value="show-task" />
            <input type="hidden" name="tid" value="<?= $tid ?>" />
            <input type="hidden" name="move-task" value="true" />
            <button type="submit">Move task</button>
        </form>
        <br>
        <br>
        <form id="form-redescribe" action="/?action=update-task-description" method="POST" hidden>
            <input type="hidden" name="tid" value="<?= $tid ?>" />
            <label for="description" >Description:</label><br>
            <input type="text" name="description" value="<?= $task['description'] ?>" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const form_redescribe = document.querySelector("#form-redescribe");
            document.querySelector("#edit").addEventListener("click", function() {
                if (form_redescribe.hidden) {
                    form_redescribe.hidden = false;
                } else {
                    form_redescribe.hidden = true;
                }
            });
        </script>
        <?php endif; ?>
    </section>

    <section class="task-notes">
        <h2>Notes</h2>
        <button type="button" id="btn-new-note">New Note</button>
        <br><br>
        <form id="form-new-note" action="/?action=add-note-to-task" method="POST" hidden>
            <input type="hidden" name="tid" value="<?= $tid ?>" />
            <textarea name="note" rows="6" required></textarea>
            <button type="submit">Save</button>
        </form>
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
        <?php foreach ($notes as $note): ?>
            <?php
            $time = dtEastern($note['created']);
            $content = preg_replace("/(pid:(\d+))/", "<a href='/?action=show-project&pid=$2'>$1</a>", $note['content']);
            $content = preg_replace("/(tid:(\d+))/", "<a href='/?action=show-task&tid=$2'>$1</a>", $content);
            ?>
            <h3><?= $time ?></h3>
            <pre><?= $content ?></pre>
        <?php endforeach; ?>
    </section>

<?php include "../view/footer.php" ?>

