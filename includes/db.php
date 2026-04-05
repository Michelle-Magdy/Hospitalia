<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        if (DB_CONNECTION !== 'pgsql') {
            if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
                http_response_code(503);
                header('Content-Type: text/html; charset=UTF-8');
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Database</title></head><body style="font-family:sans-serif;margin:2rem;">';
                echo '<h1>Unsupported database</h1><p>Set <code>DB_CONNECTION=pgsql</code> in <code>.env</code>. This app only supports PostgreSQL.</p></body></html>';
                exit;
            }
            throw new RuntimeException('Only DB_CONNECTION=pgsql is supported.');
        }
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            DB_HOST,
            DB_PORT,
            DB_DATABASE
        );
        try {
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
                throw $e;
            }
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            $msg = $e->getMessage();
            $hint = 'Check <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, and <code>DB_PASSWORD</code> in your <code>.env</code> file.';
            if (str_contains($msg, 'password authentication failed')) {
                $hint = 'PostgreSQL rejected the password. Set <code>DB_PASSWORD</code> in <code>.env</code> to match <code>DB_USERNAME</code> in PostgreSQL.';
            } elseif (str_contains($msg, 'does not exist') && str_contains(strtolower($msg), 'database')) {
                $hint = 'Create the database (in psql or pgAdmin): <code>CREATE DATABASE ' . htmlspecialchars(DB_DATABASE, ENT_QUOTES, 'UTF-8') . ';</code>';
            }
            $escHost = htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8');
            $escDb = htmlspecialchars(DB_DATABASE, ENT_QUOTES, 'UTF-8');
            $escUser = htmlspecialchars(DB_USERNAME, ENT_QUOTES, 'UTF-8');
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Database connection</title></head><body style="font-family:system-ui,sans-serif;max-width:36rem;margin:2rem auto;padding:0 1rem;">';
            echo '<h1 style="color:#a63d3d;">Cannot connect to PostgreSQL</h1>';
            echo '<p>' . $hint . '</p>';
            echo '<p style="color:#555;font-size:0.9rem;">Using host <strong>' . $escHost . '</strong>, database <strong>' . $escDb . '</strong>, user <strong>' . $escUser . '</strong>.</p>';
            echo '</body></html>';
            exit;
        }
        init_schema($pdo);
    }
    return $pdo;
}

function init_schema(PDO $pdo): void {
    // All hms_* tables avoid clashing with other apps in the same database (e.g. Laravel).
    $tables = [
        <<<SQL
        CREATE TABLE IF NOT EXISTS hms_users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role VARCHAR(20) NOT NULL CHECK (role IN ('user','admin')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS hms_doctors (
            id SERIAL PRIMARY KEY,
            name TEXT NOT NULL,
            specialty TEXT,
            phone TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS hms_patients (
            id SERIAL PRIMARY KEY,
            name TEXT NOT NULL,
            phone TEXT,
            email TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS hms_schedules (
            id SERIAL PRIMARY KEY,
            doctor_id INTEGER NOT NULL REFERENCES hms_doctors(id) ON DELETE CASCADE,
            day_of_week TEXT NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            notes TEXT
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS hms_appointments (
            id SERIAL PRIMARY KEY,
            doctor_id INTEGER NOT NULL REFERENCES hms_doctors(id) ON DELETE CASCADE,
            patient_id INTEGER NOT NULL REFERENCES hms_patients(id) ON DELETE CASCADE,
            schedule_id INTEGER REFERENCES hms_schedules(id) ON DELETE SET NULL,
            appt_datetime TIMESTAMP NOT NULL,
            status TEXT NOT NULL DEFAULT 'scheduled',
            notes TEXT
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS hms_bills (
            id SERIAL PRIMARY KEY,
            patient_id INTEGER NOT NULL REFERENCES hms_patients(id) ON DELETE CASCADE,
            appointment_id INTEGER REFERENCES hms_appointments(id) ON DELETE SET NULL,
            amount NUMERIC(12,2) NOT NULL,
            description TEXT,
            paid SMALLINT NOT NULL DEFAULT 0,
            bill_date DATE DEFAULT CURRENT_DATE
        )
        SQL,
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    // Seed one administrator when no admin exists (change password after deploy).
    $count = (int) $pdo->query("SELECT COUNT(*) FROM hms_users WHERE role = 'admin'")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $st = $pdo->prepare("INSERT INTO hms_users (username, password_hash, role) VALUES (?, ?, 'admin')");
        $st->execute(['admin', $hash]);
    }
}
