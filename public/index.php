<?php

require_once "../lib/devtools.php";
require_once "../lib/db.php";

$pdo = dbConnect();

$view = $_GET['view'] ?? "default";

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

        <tr>
            <td><a href="/add-project">Add</a></td>
        </tr>

    </table>

</section>

<section class='right'>
    <p>The Right Section</p>
</section

<?php include "footer.php" ?>

