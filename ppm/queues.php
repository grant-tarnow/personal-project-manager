<?php include "../view/header.php" ?>

<main class="queues">

    <section>

        <h2>My Queue</h2>
        <?php include "../view/queue_table.php"; ?>

    </section>

    <section id="table-selector">

        <h2 id="due" class="<?= $table == 'due' ? 'selected' : '' ?>" >Due Queue</h2>

        <h2 id="active" class="<?= $table == 'active' ? 'selected' : '' ?>" >Active Projects</h2>

        <script>
            document.querySelector("#due").addEventListener("click", function dueTable(e) {
                window.location = "/?action=queues&table=due";
            });
            document.querySelector("#active").addEventListener("click", function dueTable(e) {
                window.location = "/?action=queues&table=active";
            });
        </script>

    </section>

    <section class="<?= $table == 'due' ? '' : 'hidden' ?>">

        <h2>
            Date Queue (due within
            <select id="weeks" name="weeks">
                <option value=1 <?= $weeks == 1 ? "selected" : "" ?>>1 week</option>
                <option value=2 <?= $weeks == 2 ? "selected" : "" ?>>2 weeks</option>
                <option value=3 <?= $weeks == 3 ? "selected" : "" ?>>3 weeks</option>
                <option value=4 <?= $weeks == 4 ? "selected" : "" ?>>4 weeks</option>
                <option value=0 <?= $weeks == 0 ? "selected" : "" ?>>anytime</option>
            </select>
            )
        </h2>
        <script>
            const time_selector = document.querySelector("#weeks");
            time_selector.addEventListener("change", function(e) {
                window.location = "/?weeks=" + time_selector.value;
            });
        </script>

        <?php include "../view/due_table.php"; ?>

    </section>

    <section class="<?= $table == 'active' ? '' : 'hidden' ?>">

        <h2>Active Projects</h2>
        <?php include "../view/project_table.php"; ?>

    </section>

</main>

<?php include "../view/footer.php" ?>

