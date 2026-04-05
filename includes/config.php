<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'freelance_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',    'GetHired');
define('BASE_URL',    'http://localhost/freelance-platform'); // no trailing slash
define('UPLOAD_DIR',  __DIR__ . '/../uploads/profile_images/');
define('UPLOAD_URL',  BASE_URL . '/uploads/profile_images/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024);   // 2 MB
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

define('SESSION_TIMEOUT', 1800); // 30 min in seconds

define('JOBS_PER_PAGE', 9);

error_reporting(E_ALL);
ini_set('display_errors', 1);
