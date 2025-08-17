-- creation schema for the tool

CREATE TABLE IF NOT EXISTS projects (
    project_id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    priority INTEGER NOT NULL,
    status TEXT NOT NULL,
    created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS tasks (
    task_id INTEGER PRIMARY KEY,
    project_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    status TEXT NOT NULL,
    next INTEGER DEFAULT 0 NOT NULL,
    created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (project_id)
        REFERENCES projects (project_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS status_updates (
    update_id INTEGER PRIMARY KEY,
    project_id INTEGER,
    task_id INTEGER,
    status TEXT NOT NULL,
    created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (project_id)
        REFERENCES projects (project_id)
        ON DELETE CASCADE,
    FOREIGN KEY (task_id)
        REFERENCES tasks (task_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notes (
    note_id INTEGER PRIMARY KEY,
    project_id INTEGER,
    task_id INTEGER,
    content TEXT NOT NULL,
    created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (project_id)
        REFERENCES projects (project_id)
        ON DELETE CASCADE,
    FOREIGN KEY (task_id)
        REFERENCES tasks (task_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS links (
    link_id INTEGER PRIMARY KEY,
    project_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    path TEXT NOT NULL,
    created TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated TEXT DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (project_id)
        REFERENCES projects (project_id)
        ON DELETE CASCADE
);
