<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
--><?php

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;               // created once, then reused

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw on SQL errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements
            ]);
            // Make MySQL's clock (CURRENT_TIMESTAMP) use the same timezone as PHP,
            // otherwise "created at" times would be a few hours out.
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        } catch (PDOException $ex) {
            error_log('DB connection failed: ' . $ex->getMessage());
            http_response_code(500);
            exit('Database connection failed. Check includes/config.php and make sure MySQL is running.');
        }
    }
    return $pdo;
}
