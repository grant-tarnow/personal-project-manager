<?php include "../view/header.php" ?>

<main class="project-view">
    <section class="project-details">
        <h3>Project | <span id="priority">P<?= $project['priority'] ?></span></h3>
        <form id="form-priority" action="/?action=update-project-priority" method="POST" hidden>
            <input type="hidden" name="pid" value=<?= $pid ?> />
            <label for="priority">Select a priority:</label><br>
            <select id="priority" name="priority" required>
                <option value=0>0</option>
                <option value=1>1</option>
                <option value=2>2</option>
                <option value=3 selected>3</option>
                <option value=4>4</option>
                <option value=5>5</option>
            </select>
            <br>
            <br>
            <label for="priority-note">Note:</label><br>
            <textarea id="priority-note" name="note" rows="6" required></textarea>
            <button type="submit">Save</button>
        </form>
        <script>
            const form_priority = document.querySelector("#form-priority");
            document.querySelector("#priority").addEventListener("click", function() {
                if (form_priority.hidden) {
                    form_priority.hidden = false;
                } else {
                    form_priority.hidden = true;
                }
            });
        </script>
        <h2><?= $project['title'] ?></h2>
        <h3><span class="due-date-display">Due date: <?= $project['due'] ? $project['due'] : "None" ?></span></h3>
        <div id="due-date-div" hidden>
            <form action="/?action=update-project-due" method="POST" style="display: inline;" >
                <input type="hidden" name="pid" value=<?= $pid ?> />
                <label for="due-date">Enter due date:</label>
                <input type="date" id="due-date" name="due-date" <?= $project['due'] ? "value='{$project['due']}'" : "" ?>/>
                <button type="submit">Save</button>
            </form>
            <form class="just-btn" action="/?action=clear-project-due" method="POST">
                <input type="hidden" name="pid" value=<?= $pid ?> />
                <button type="submit" >Clear</button>
            </form>
        </div>
        <script>
            const due_date = document.querySelector(".due-date-display");
            const due_date_div = document.querySelector("#due-date-div");
            due_date.addEventListener("click", function(e){
                if (due_date_div.hidden) {
                    due_date_div.hidden = false;
                } else {
                    due_date_div.hidden = true;
                }
            });
        </script>
        <h2><span class="status-display" id="project-status" style="color: <?= $status_color ?>;"><?= $project['status'] ?></span>
        |
        <?php if (checkQueued("project", $pid)): ?>
        QUEUED
        <?php else: ?>
            <form class="just-btn" action="/?action=queue-project" method="POST" >
                <input type="hidden" name="pid" value=<?= $pid ?>>
                <button type="submit" >queue</button>
            </form>
        <?php endif; ?>
        </h2>
        <form id="form-status-update" action="/?action=update-project-status" method="POST" hidden>
            <input type="hidden" name="pid" value=<?= $pid ?> />
            <label for="status">Select a status:</label><br>
            <select id="status" name="status" required>
                <option value="NOT STARTED">NOT STARTED</option>
                <option value="IN PROGRESS">IN PROGRESS</option>
                <option value="ON HOLD">ON HOLD</option>
                <option value="ABANDONED">ABANDONED</option>
                <option value="COMPLETE">COMPLETE</option>
            </select>
            <br><br>
            <label for="status-note">Note:</label><br>
            <textarea id="status-note" name="note" rows="6" required></textarea>
            <button type="submit">Save</button>
        </form>
        <script>
            const status_display = document.querySelector("#project-status");
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
        <button id="btn-update-title" type="button" >Update title</button>
        <br>
        <form id="form-retitle" action="/?action=update-project-title" method="POST" hidden>
            <br>
            <input type="hidden" name="pid" value=<?= $pid ?> />
            <label for="title">Title:</label><br>
            <input type="text" id="title" name="title" value="<?= $project['title'] ?>" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const form_retitle = document.querySelector("#form-retitle");
            document.querySelector("#btn-update-title").addEventListener("click", function() {
                if (form_retitle.hidden) {
                    form_retitle.hidden = false;
                } else {
                    form_retitle.hidden = true;
                }
            });
        </script>
        <hr>
        <h2>Links</h2>
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
        <form id="form-new-link" action="/?action=add-link-from-project" method="POST" hidden>
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

    <section class="project-tasks">
        <h2>Task List</h2>
        <button type="button" id="btn-new-task">New Task</button>
        <br>
        <br>
        <form id="form-new-task" action="/?action=add-task" method="POST" hidden>
            <input type="hidden" name="pid" value=<?= $pid ?> />
            <label for="task-description" >Description:</label><br>
            <input type="text" id="task-description" name="description" required>
            <button type="submit">Save</button>
        </form>
        <script>
            const btn_task = document.querySelector("#btn-new-task");
            const form_task = document.querySelector("#form-new-task");
            btn_task.addEventListener("click", function() {
                if (form_task.hidden) {
                    form_task.hidden = false;
                } else {
                    form_task.hidden = true;
                }
            });
        </script>
        <?php
            foreach ($incomplete_tasks as $task) {
                include "../view/task_card.php";
            }
        ?>
        <br>
        <button type="button" id="btn-complete-tasks">Show Complete and Abandoned</button>
        <div id="complete-tasks" hidden>
            <h2>Complete and Abandoned Tasks</h2>
            <?php
                foreach ($complete_tasks as $task) {
                    include "../view/task_card.php";
                }
            ?>
        </div>
        <script>
            const btn_complete = document.querySelector("#btn-complete-tasks");
            const complete = document.querySelector("#complete-tasks");
            btn_complete.addEventListener("click", function() {
                if (complete.hidden) {
                    complete.hidden = false;
                    btn_complete.innerHTML = "Hide Complete and Abandoned";
                } else {
                    complete.hidden = true;
                    btn_complete.innerHTML = "Show Complete and Abandoned";
                }
            });
        </script>
    </section>

    <section class="project-notes">
        <h2>Notes</h2>
        <button type="button" id="btn-new-note">New Note</button>
        <br>
        <br>
        <form id="form-new-note" action="/?action=add-note-to-project" method="POST" hidden>
            <input type="hidden" name="pid" value=<?= $pid ?> />
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

</main>

<?php include "../view/footer.php" ?>
