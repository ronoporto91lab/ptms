<?php
declare(strict_types=1);

final class Database
{
    public static function connect(): PDO
    {
        $credentialsFile = __DIR__ . '/credentials.php';
        $credentials = file_exists($credentialsFile) ? require $credentialsFile : [];

        $host = (string) ($credentials['host'] ?? self::environment('DB_HOST', '127.0.0.1'));
        $port = (string) ($credentials['port'] ?? self::environment('DB_PORT', '3306'));
        $database = (string) ($credentials['database'] ?? self::environment('DB_NAME', 'ptms'));
        $username = (string) ($credentials['username'] ?? self::environment('DB_USER', 'root'));
        $password = (string) ($credentials['password'] ?? self::environment('DB_PASSWORD', ''));

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            throw new RuntimeException(
                'The database connection failed. Check config/database.php and make sure MySQL is running.'
            );
        }
    }

    private static function environment(string $name, string $default): string
    {
        $value = getenv($name);

        return $value === false || $value === '' ? $default : $value;
    }
}
