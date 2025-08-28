<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";

$pdo = dbConnect();

$view = $_GET['view'] ?? "default";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, "priority", FILTER_VALIDATE_INT);

    if ($title) { // not checking $priority because 0 is falsey
        addProject($pdo, $title, $priority);
    }

}

$projects = getProjects($pdo, $view);

?>

<?php include "header.php" ?>

<section class="left">

    <h2>Project Views</h2>
    <p><a href="/?view=default">Default</a></p>
    <p><a href="/?view=active">Active</a></p>
    <p><a href="/?view=hold">On Hold</a></p>
    <p><a href="/?view=incomplete">Incomplete</a></p>
    <p><a href="/?view=complete">Complete</a></p>
    <p><a href="/?view=all">All</a></p>

</section>

<section class="center">

    <h2>Projects List</h2>

    <table>
        <tr>
            <th>Priority</th>
            <th>Status</th>
            <th>Title</th>
        </tr>

    <?php foreach ($projects as $prj): ?>
        <tr>
            <?php
            $a_tag_open = "<a class='link' href='/project.php?pid={$prj['project_id']}'>";
            echo "<td>{$a_tag_open}{$prj['priority']}</a></td>";
            echo "<td>{$a_tag_open}{$prj['status']}</a></td>";
            echo "<td>{$a_tag_open}{$prj['title']}</a></td>";
            ?>
            </a>
        </tr>
    <?php endforeach; ?>

    </table>

    <br>

    <button type="button" id="btn-add-project">New Project</button>
    <br><br>
    <form id="form-add-project" action="" method="POST" style="display: none;">
        <label for="title">Title:</label>
        <input type="text" name="title" required>
        <label for="priority">Select a priority:</label>
        <select name="priority" required>
            <option value=0>0</option>
            <option value=1>1</option>
            <option value=2>2</option>
            <option value=3 selected>3</option>
            <option value=4>4</option>
            <option value=5>5</option>
        </select>
        <br><br>
        <br>
        <button type="submit">Save</button>
    </form>
    <script>
        const btn_prj = document.querySelector("#btn-add-project");
        const form_prj = document.querySelector("#form-add-project");
        btn_prj.addEventListener("click", function() {
            if (form_prj.style.display == "none") {
                form_prj.style.display = "block";
            } else {
                form_prj.style.display = "none";
            }
        });
    </script>

</section>

<section class='right'>
    <p>The Right Section</p>
</section

<?php include "footer.php" ?>

