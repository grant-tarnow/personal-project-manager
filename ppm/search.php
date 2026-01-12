<?php include "../view/header.php" ?>

<main class="search-page">
    <form class="search-bar" action="." method="GET" >
        <input type="hidden" name="action" value="search" />
        <input type="text" id="query" name="query" value="<?= $query ?>" required>
        <span>
            <input type="checkbox" id="search-projects" name="search-projects" <?= $check_project ?> />
            <label for="search-projects">Projects</label>
        </span>
        <span>
            <input type="checkbox" id="search-tasks" name="search-tasks" <?= $check_task ?> />
            <label for="search-tasks">Tasks</label>
        </span>
        <span>
            <input type="checkbox" id="search-notes" name="search-notes" <?= $check_note ?> />
            <label for="search-notes">Notes</label>
        </span>
        <button type="submit" class="just-btn" >Search</button>
    </form>
    <br>
    <section class="search-results">
        <?php if (isset($projects)): ?>
            <h2>Projects</h2>
            <?php include "../view/project_table.php" ?>
            <br>
        <?php endif; ?>
        <?php if (isset($tasks)): ?>
            <h2>Tasks</h2>
            <?php include "../view/task_table.php" ?>
            <br>
        <?php endif; ?>
        <?php if (isset($notes)): ?>
            <h2>Notes</h2>
            <?php include "../view/note_table.php" ?>
        <?php endif; ?>
    </section>
</main>
