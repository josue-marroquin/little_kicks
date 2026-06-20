<?php

declare(strict_types=1);

namespace Kicks\App;

use PDO;

final class SessionRepository
{
    private PDO $pdo;

    public function __construct(Database $db)
    {
        $this->pdo = $db->pdo();
    }

    public function createSession(string $id, string $startedAt): void
    {
        $sql = 'INSERT INTO sessions (id, started_at) VALUES (:id, :started_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'started_at' => $startedAt]);
    }

    public function addMovement(string $sessionId, string $occurredAt): void
    {
        $sql = 'INSERT INTO movements (session_id, occurred_at) VALUES (:session_id, :occurred_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['session_id' => $sessionId, 'occurred_at' => $occurredAt]);
    }

    public function finishSession(string $sessionId, ?string $endedAt, ?string $notes): void
    {
        $sql = 'UPDATE sessions SET ended_at = :ended_at, notes = :notes WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ended_at' => $endedAt, 'notes' => $notes, 'id' => $sessionId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function listSessions(): array
    {
        $sql = 'SELECT s.id, s.started_at, s.ended_at, s.notes, COUNT(m.id) AS movements_count
                FROM sessions s
                LEFT JOIN movements m ON m.session_id = s.id
                GROUP BY s.id
                ORDER BY s.started_at DESC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function getSession(string $id): ?array
    {
        $sql = 'SELECT id, started_at, ended_at, notes FROM sessions WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $session = $stmt->fetch();
        if (! $session) {
            return null;
        }

        $sql = 'SELECT occurred_at FROM movements WHERE session_id = :id ORDER BY occurred_at ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $movements = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $session['movements'] = $movements;
        return $session;
    }
}
