<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";
require_once "../lib/utility.php";

$view = $_GET['view'] ?? "default";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);

    if ($title) { // not checking $priority because 0 is falsey
        addProject($title, $priority);
    }

}

$projects = getProjects($view);
?>

<?php include "header.php" ?>

<style>
    .project-list {
        display: flex;
        flex-flow: row nowrap;
        justify-content: space-between;
        gap: 30px;
        width: 100%;
    }

    .project-views {
        flex: 1;
    }
    .project-table {
        flex: 9;
    }
</style>

<section class="project-list">

    <aside class="project-views">

        <h2>Views</h2>
        <p><a href="/project-list.php?view=default">Default</a></p>
        <p><a href="/project-list.php?view=active">Active</a></p>
        <p><a href="/project-list.php?view=hold">On Hold</a></p>
        <p><a href="/project-list.php?view=incomplete">Incomplete</a></p>
        <p><a href="/project-list.php?view=complete">Complete</a></p>
        <p><a href="/project-list.php?view=all">All</a></p>

    </aside>

    <main class="project-table">

        <table>
            <tr>
                <th>Pri</th>
                <th>Due</th>
                <th>Status</th>
                <th>Title</th>
                <th>Next</th>
            </tr>

        <?php foreach ($projects as $prj): ?>
            <?php
            $prj_color = statusColor($prj['status']);
            $next = getNextOfProject($prj['project_id']);
            if (!$next) {
                $next = ['description' => 'None'];
            }
            ?>
            <tr id='<?= "prj{$prj['project_id']}" ?>'>
                <td><?= $prj['priority'] ?></td>
                <td style="min-width: 10ch;"><?= $prj['due'] ?></td>
                <td style='color: <?= $prj_color ?>'><?= $prj['status'] ?></td>
                <td><?= $prj['title'] ?></td>
                <td><?= $next['description'] ?></td>
            </tr>
            <script>
                document.querySelector("<?= "#prj{$prj['project_id']}" ?>").addEventListener("click", function() {
                    window.location = "<?= "/project.php?pid={$prj['project_id']}" ?>";
                });
            </script>
        <?php endforeach; ?>

        </table>

        <br>

        <button type="button" id="btn-add-project">New Project</button>
        <br>
        <br>
        <form id="form-add-project" action="" method="POST" hidden>
            <label for="title" style="display: block;">Title:</label>
            <input type="text" name="title" size="60" required>
            <label for="priority" style="display: block;">Select a priority:</label>
            <select name="priority" required>
                <option value=0>0</option>
                <option value=1>1</option>
                <option value=2>2</option>
                <option value=3 selected>3</option>
                <option value=4>4</option>
                <option value=5>5</option>
            </select>
            <button type="submit">Save</button>
        </form>
        <script>
            const btn_prj = document.querySelector("#btn-add-project");
            const form_prj = document.querySelector("#form-add-project");
            btn_prj.addEventListener("click", function() {
                if (form_prj.hidden) {
                    form_prj.hidden = false;
                } else {
                    form_prj.hidden = true;
                }
            });
        </script>

    </main>

</section>

<?php include "footer.php" ?>

