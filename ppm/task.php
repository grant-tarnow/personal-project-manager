<?php include "../view/header.php" ?>

<style>
    .task {
        display: flex;
        flex-flow: row nowrap;
        justify-content: space-between;
        gap: 50px;
        width: 100%;
    }
    .project {
        flex: 1;
    }
    .details {
        flex: 2;
    }
    .notes {
        flex: 2;
    }
    #status-display {
        color: <?= $status_color ?>;
    } #status-display:hover {
        cursor: pointer;
    }
</style>

<main class="task">

    <section class="project">
        <h3>Parent Project:</h3>
        <h2><a href='/?action=show-project&pid=<?= $pid ?>'><?= $project['title'] ?></a></h2>
        <h3>(P<?= $project['priority'] ?>)</h3>
        <hr>
        <h2>Links of Parent Project</h2>
        <?php foreach ($links as $link): ?>
            <?php if (filter_var($link['path'], FILTER_VALIDATE_URL)): ?>
                <p><a href='<?= $link['path'] ?>'><?= $link['description'] ?></a></p>
            <?php else: ?>
                <pre><?= "{$link['description']}\n\t{$link['path']}" ?></pre>
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="button" id="btn-new-link">New Link</button>
        <br>
        <br>
        <form id="form-new-link" action="/?action=add-link-from-task" method="POST" style="display: none;">
            <input type="hidden" name="tid" value=<?= $tid ?> />
            <input type="hidden" name="pid" value=<?= $pid ?> />
            <label for="link-description" style="display: block;">Description:</label>
            <input type="text" name="link-description" style="width: 100%;" required>
            <label for="link-path" style="display: block;">Path:</label>
            <input type="text" name="link-path" style="width: 100%;" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const btn_link = document.querySelector("#btn-new-link");
            const form_link = document.querySelector("#form-new-link");
            btn_link.addEventListener("click", function() {
                if (form_link.style.display == "none") {
                    form_link.style.display = "block";
                } else {
                    form_link.style.display = "none";
                }
            });
        </script>
    </section>

    <section class="details">
        <h2><?= $task['description'] ?></h2>
        <h3><span class='due-date-display'>Due date: <?= $task['due'] ? $task['due'] : "None" ?></span></h3>
        <div id="due-date-div" hidden>
            <form action="/?action=update-task-due" method="POST" style="display: inline-block;">
                <input type="hidden" name="tid" value=<?= $tid ?> />
                <label for='due-date'>Enter due date:</label>
                <input type='date' id='due-date' name='due-date' <?= $task['due'] ? "value='{$task['due']}'" : "" ?>/>
                <button type='submit'>Save</button>
            </form>
            <form action="/?action=clear-task-due" method="POST" style="display: inline-block;">
                <input type='hidden' name='tid' value=<?= $tid ?> />
                <button type='submit'>Clear</button>
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
            <?php if ($task['next']): ?>
                <span style='color: firebrick;'>NEXT</span>
            <?php else: ?>
                <form  action='/?action=nextify' method='POST' style='display: inline;'>
                    <input type="hidden" name="tid" value=<?= $tid ?> />
                    <input type="hidden" name="pid" value=<?= $pid ?> />
                    <button type='submit' class="solo-btn">nextify</button>
                </form>
            <?php endif; ?>
            |
            <span id="status-display"><?= $task['status'] ?></span>
            |
            <?php if (checkQueued("task", $tid)): ?>
                QUEUED
            <?php else: ?>
                <form action="/?action=queue-task" method='POST' style='display: inline;'>
                    <input type="hidden" name="tid" value=<?= $tid ?> />
                    <button type='submit' class="solo-btn">queue</button>
                </form>
            <?php endif; ?>
        </h2>
        <form id="form-status-update" action="/?action=update-task-status" method="POST" hidden>
            <input type="hidden" name="tid" value=<?= $tid ?> />
            <label for="status" style="display: block;">Select a status:</label>
            <select name="status" id="status" required>
                <option value="NOT STARTED">NOT STARTED</option>
                <option value="IN PROGRESS">IN PROGRESS</option>
                <option value="ON HOLD">ON HOLD</option>
                <option value="ABANDONED">ABANDONED</option>
                <option value="COMPLETE">COMPLETE</option>
            </select>
            <br><br>
            <label for="status-note" style="display: block;">Note:</label>
            <textarea id="status-note" name="note" rows="6" style="width: 100%;" required></textarea>
            <button type="submit">Save</button>
        </form>
        <script>
            const status_display = document.querySelector("#status-display");
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
                <input type='hidden' name='tid' value='<?= $tid ?>' />
                <?php foreach ($projects as $prj): ?>
                    <div>
                        <input type='radio' id='<?= $prj['project_id'] ?>' name='pid' value=<?= $prj['project_id'] ?> required>
                        <label style='display: inline;' for='<?= $prj['project_id'] ?>'><?= "[P{$prj['priority']}] {$prj['title']}" ?></label>
                    </div>
                <?php endforeach; ?>
                <button type="submit">Submit</button>
            </form>
            <p><a href="/?action=show-task&tid=<?= $tid ?>">Cancel</a></p>
        <?php else: ?>
        <button id="edit" type="button">Edit description</button>
        <form action="." method="GET" style="display: inline-block;">
            <input type='hidden' name='action' value='show-task' />
            <input type='hidden' name='tid' value='<?= $tid ?>' />
            <input type='hidden' name='move-task' value='true' />
            <button type="submit">Move task</button>
        </form>
        <br>
        <br>
        <form id="form-redescribe" action="/?action=update-task-description" method="POST" hidden>
            <input type='hidden' name='tid' value='<?= $tid ?>' />
            <label for="description" style="display: block;">Description:</label>
            <input type="text" name="description" style="width: 100%;" value="<?= $task['description'] ?>" required>
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

    <section class="notes">
        <h2>Notes</h2>
        <button type="button" id="btn-new-note">New Note</button>
        <br><br>
        <form id="form-new-note" action="/?add-note-to-task" method="POST" hidden>
            <input type='hidden' name='tid' value='<?= $tid ?>' />
            <textarea name="note" rows="6" style="width: 100%;" required></textarea>
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

