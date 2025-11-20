<?php include "../view/header.php" ?>

<section class="project-list">

    <aside class="project-views">

        <h2>Views</h2>
        <p><a href="?action=list-projects&view=default">Default</a></p>
        <p><a href="?action=list-projects&view=active">Active</a></p>
        <p><a href="?action=list-projects&view=hold">On Hold</a></p>
        <p><a href="?action=list-projects&view=incomplete">Incomplete</a></p>
        <p><a href="?action=list-projects&view=complete">Complete</a></p>
        <p><a href="?action=list-projects&view=all">All</a></p>

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
            <tr id="<?= "prj{$prj['project_id']}" ?>">
                <td><?= $prj['priority'] ?></td>
                <td class="due"><?= $prj['due'] ?></td>
                <td style="color: <?= $prj_color ?>"><?= $prj['status'] ?></td>
                <td><?= $prj['title'] ?></td>
                <td><?= $next['description'] ?></td>
            </tr>
            <script>
                document.querySelector("<?= "#prj{$prj['project_id']}" ?>").addEventListener("click", function() {
                    window.location = "<?= "/?action=show-project&pid={$prj['project_id']}" ?>";
                });
            </script>
        <?php endforeach; ?>

        </table>

        <br>

        <button type="button" id="btn-add-project">New Project</button>
        <br>
        <br>
        <form id="form-add-project" action="/?action=add-project" method="POST" hidden>
            <label for="title" >Title:</label><br>
            <input type="text" id="title" name="title" size="60" required>
            <label for="priority" >Select a priority:</label><br>
            <select id="priority" name="priority" required>
                <option value=0>0</option>
                <option value=1>1</option>
                <option value=2>2</option>
                <option value=3 selected>3</option>
                <option value=4>4</option>
                <option value=5>5</option>
            </select><br>
            <br>
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

<?php include "../view/footer.php" ?>

