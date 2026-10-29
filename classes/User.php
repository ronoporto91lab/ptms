<?php
declare(strict_types=1);

final class User
{
    public function __construct(private PDO $database) {}

    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT user_id, name, email, password, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM users WHERE email = :email'
        );
        $statement->execute(['email' => strtolower(trim($email))]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function register(array $data): int
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'INSERT INTO users (name, email, password, role, contact_number)
                 VALUES (:name, :email, :password, :role, :contact_number)'
            );
            $statement->execute([
                'name' => trim((string) $data['name']),
                'email' => strtolower(trim((string) $data['email'])),
                'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
                'role' => (string) $data['role'],
                'contact_number' => trim((string) $data['contact_number']),
            ]);
            $userId = (int) $this->database->lastInsertId();

            $this->database->commit();
            return $userId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }
}
