<?php include "../view/header.php" ?>

<main class="queues">

    <section class="date-queue">
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
        <table>
            <tr>
                <th>Type</th>
                <th>Due</th>
                <th>Status</th>
                <th>Title/Description</th>
            </tr>
            <?php foreach ($date_queue as $item): ?>
                <?php
                $type = "";
                $id = "";
                $status = "";
                $due = "";
                $tod = "";
                $url = "";
                $color = "";
                if (array_key_exists('task_id', $item)) {
                    $type = "Task";
                    $id = "prj{$item['task_id']}";
                    $status = $item['status'];
                    $due = $item['due'];
                    $tod = $item['description'];
                    $url = "/?action=show-task&tid={$item['task_id']}";
                    $color = statusColor($item['status']);
                } else {
                    $type = "Project";
                    $id = "prj{$item['project_id']}";
                    $status = $item['status'];
                    $due = $item['due'];
                    $tod = $item['title'];
                    $url = "/?action=show-project&pid={$item['project_id']}";
                    $color = statusColor($item['status']);
                }
                ?>
                <tr id="<?= $id ?>">
                    <td><?= $type ?></td>
                    <td class="due"><?= $due ?></td>
                    <td style="color: <?= $color ?>;"><?= $status ?></td>
                    <td><?= $tod ?></td>
                </tr>
                <script>
                    document.querySelector("#<?= $id ?>").addEventListener("click", function() {
                        window.location = "<?= $url ?>";
                    });
                </script>
            <?php endforeach; ?>
        </table>
    </section>

    <section class="my-queue">

        <h2>My Queue</h2>

        <table>
            <tr>
                <th>Type</th>
                <th>Due</th>
                <th>Status</th>
                <th>Title/Description</th>
                <th>Manage</th>
            </tr>

        <?php foreach ($queue as $item): ?>
            <?php
            $type = "";
            $status = "";
            $due = "";
            $tod = "";
            $url = "";
            $color = "";
            if ($item['project_id']) {
                $type = "Project";
                $prj = getProject($item['project_id']);
                $status = $prj['status'];
                $due = $prj['due'];
                $tod = $prj['title'];
                $url = "/?action=show-project&pid={$item['project_id']}";
                $color = statusColor($prj['status']);
            } else if ($item['task_id']) {
                $type = "Task";
                $task = getTask($item['task_id']);
                $status = $task['status'];
                $due = $task['due'];
                $tod = $task['description'];
                $url = "/?action=show-task&tid={$item['task_id']}";
                $color = statusColor($task['status']);
            }
            ?>
            <tr id="<?= "queue{$item['position']}" ?>">
                <td><?= $type ?></td>
                <td class="due"><?= $due ?></td>
                <td style="color: <?= $color ?>"><?= $status ?></td>
                <td><?= $tod ?></td>
                <td>
                    <form action="" method="POST" class="just-btn">
                        <input type="hidden" name="pos-up" value="<?= $item['position'] ?>">
                        <button type="submit" >up</button>
                    </form>
                    <form action="" method="POST" class="just-btn">
                        <input type="hidden" name="pos-rm" value="<?= $item['position'] ?>">
                        <button type="submit" >rm</button>
                    </form>
                    <form action="" method="POST" class="just-btn">
                        <input type="hidden" name="pos-dn" value="<?= $item['position'] ?>">
                        <button type="submit" >dn</button>
                    </form>
                </td>
            </tr>
            <script>
                document.querySelector("<?= "#queue{$item['position']}"; ?>").addEventListener("click", function() {
                    window.location = "<?= $url; ?>";
                });
            </script>
        <?php endforeach; ?>
        </table>
    </section>

</main>

<?php include "../view/footer.php" ?>

