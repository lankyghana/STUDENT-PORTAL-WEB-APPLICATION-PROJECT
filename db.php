<?php
/**
 * Database connection and setup.
 * Uses SQLite via PDO — the database file (students.db) and the
 * students table are created automatically on first run.
 */

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . __DIR__ . '/students.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS students (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                profile_image    TEXT,
                first_name       TEXT NOT NULL,
                middle_name      TEXT,
                last_name        TEXT NOT NULL,
                email            TEXT NOT NULL,
                date_of_birth    TEXT NOT NULL,
                gender           TEXT NOT NULL,
                phone_number     TEXT NOT NULL,
                address          TEXT NOT NULL,
                state_of_origin  TEXT NOT NULL,
                local_government TEXT NOT NULL,
                next_of_kin      TEXT NOT NULL,
                jamb_score       INTEGER NOT NULL,
                admission_status TEXT NOT NULL DEFAULT 'Undecided',
                created_at       TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");
    }

    return $pdo;
}

/** Escape output for safe HTML rendering. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
