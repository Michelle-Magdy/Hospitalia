<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/includes/env.php';
load_dotenv(BASE_PATH . '/.env');

session_start();

/*
 * Database — Laravel-style DB_* keys in .env (see .env.example).
 * Only pgsql is supported.
 */
define('DB_CONNECTION', strtolower(env('DB_CONNECTION', 'pgsql')));
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '5432'));
define('DB_DATABASE', env('DB_DATABASE', 'hospital_db'));
define('DB_USERNAME', env('DB_USERNAME', 'postgres'));
$dbPassword = getenv('DB_PASSWORD');
define('DB_PASSWORD', $dbPassword !== false ? $dbPassword : 'postgres');
