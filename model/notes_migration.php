<?php

require_once "db.php" ;

$pdo->beginTransaction();
$pdo->exec("ALTER TABLE notes RENAME TO old_notes");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS notes (
        note_id INTEGER PRIMARY KEY,
        note_num INTEGER NOT NULL,
        version INTEGER DEFAULT 1 NOT NULL,
        latest INTEGER DEFAULT 1 NOT NULL,
        project_id INTEGER,
        task_id INTEGER,
        content TEXT NOT NULL,
        active INTEGER DEFAULT 1 NOT NULL,
        created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (project_id)
            REFERENCES projects (project_id)
            ON DELETE CASCADE,
        FOREIGN KEY (task_id)
            REFERENCES tasks (task_id)
            ON DELETE CASCADE
    )
");

$stmt = $pdo->query("SELECT * FROM old_notes");
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notes as $note) {
    $stmt2 = $pdo->prepare("INSERT INTO
        notes (note_id, note_num, project_id, task_id, content, created, updated)
        VALUES (:id, :num, :pid, :tid, :content, :created, :updated)"
        );
    $stmt2->execute(
        ['id' => $note['note_id'],
        'num' => $note['note_id'],
        'pid' => $note['project_id'],
        'tid' => $note['task_id'],
        'content' => $note['content'],
        'created' => $note['created'],
        'updated' => $note['updated'] 
        ]);
}

$pdo->exec("DROP TABLE old_notes");

$pdo->commit();

echo "Migration complete!\n";

