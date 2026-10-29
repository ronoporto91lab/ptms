<?php
declare(strict_types=1);

final class TutoringSession
{
    public function __construct(private PDO $database) {}

    public function addTutoringSession(array $data): int
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'INSERT INTO tutoring_sessions
                 (tutor_id, student_id, subject_id, session_date, time_slot, status, location)
                 VALUES (:tutor_id, :student_id, :subject_id, :session_date, :time_slot, :status, :location)'
            );
            $statement->execute($this->parameters($data));
            $sessionId = (int) $this->database->lastInsertId();

            $this->database->commit();
            return $sessionId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    public function getTutoringSessions(): array
    {
        $statement = $this->database->prepare($this->selectQuery() .
            ' ORDER BY ts.session_id ASC');
        $statement->execute();
        return $statement->fetchAll();
    }

    public function getTutoringSession(int $id): ?array
    {
        $statement = $this->database->prepare($this->selectQuery() .
            ' WHERE ts.session_id = :session_id');
        $statement->execute(['session_id' => $id]);
        $record = $statement->fetch();
        return $record === false ? null : $record;
    }

    public function updateTutoringSession(int $id, array $data): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'UPDATE tutoring_sessions SET tutor_id=:tutor_id, student_id=:student_id,
                 subject_id=:subject_id, session_date=:session_date, time_slot=:time_slot,
                 status=:status, location=:location WHERE session_id=:session_id'
            );
            $parameters = $this->parameters($data);
            $parameters['session_id'] = $id;
            $statement->execute($parameters);

            $this->database->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteTutoringSession(int $id): bool
    {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->prepare(
                'DELETE FROM tutoring_sessions WHERE session_id = :session_id'
            );
            $statement->execute(['session_id' => $id]);

            $this->database->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    public function searchTutoringSessions(string $keyword): array
    {
        $fields = ['CAST(ts.session_id AS CHAR)','tutor.name','student.name','s.subject_name',
            'CAST(ts.session_date AS CHAR)','ts.time_slot','ts.status','ts.location'];
        $conditions = [];
        $parameters = [];
        foreach ($fields as $index => $field) {
            $name = 'keyword_' . $index;
            $conditions[] = $field . ' LIKE :' . $name;
            $parameters[$name] = '%' . $keyword . '%';
        }
        $statement = $this->database->prepare($this->selectQuery() .
            ' WHERE ' . implode(' OR ', $conditions) .
            ' ORDER BY ts.session_id ASC');
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function getTutors(): array { return $this->getUsersByRole('Tutor'); }
    public function getStudents(): array { return $this->getUsersByRole('Student'); }

    public function getSubjects(): array
    {
        $statement = $this->database->prepare(
            'SELECT subject_id, subject_name FROM subjects ORDER BY subject_name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }

    private function getUsersByRole(string $role): array
    {
        $statement = $this->database->prepare(
            'SELECT user_id, name FROM users WHERE role = :role ORDER BY name'
        );
        $statement->execute(['role' => $role]);
        return $statement->fetchAll();
    }

    private function selectQuery(): string
    {
        return 'SELECT ts.*, tutor.name AS tutor_name, student.name AS student_name,
                       s.subject_name
                FROM tutoring_sessions ts
                INNER JOIN users tutor ON tutor.user_id = ts.tutor_id
                INNER JOIN users student ON student.user_id = ts.student_id
                INNER JOIN subjects s ON s.subject_id = ts.subject_id';
    }

    private function parameters(array $data): array
    {
        return ['tutor_id'=>(int)$data['tutor_id'], 'student_id'=>(int)$data['student_id'],
            'subject_id'=>(int)$data['subject_id'], 'session_date'=>(string)$data['session_date'],
            'time_slot'=>trim((string)$data['time_slot']), 'status'=>(string)$data['status'],
            'location'=>trim((string)$data['location'])];
    }
}
