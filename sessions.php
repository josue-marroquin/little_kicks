<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function database(): PDO
{
    $environment = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
    if ($environment === false) {
        respond(['ok' => false, 'error' => 'No se pudo cargar la configuración.'], 500);
    }

    foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
        if (!array_key_exists($key, $environment)) {
            respond(['ok' => false, 'error' => 'La configuración está incompleta.'], 500);
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $environment['DB_HOST'],
        $environment['DB_PORT'],
        $environment['DB_NAME']
    );

    try {
        return new PDO($dsn, $environment['DB_USER'], $environment['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException) {
        respond(['ok' => false, 'error' => 'No se pudo conectar a la base de datos.'], 500);
    }
}

function mysqlDate(string $value): string
{
    try {
        return (new DateTimeImmutable($value))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    } catch (Exception) {
        respond(['ok' => false, 'error' => 'La sesión contiene una fecha inválida.'], 422);
    }
}

function isoDate(string $value): string
{
    return DateTimeImmutable::createFromFormat(
        'Y-m-d H:i:s',
        $value,
        new DateTimeZone('UTC')
    )->format(DATE_ATOM);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = database();

if ($method === 'GET') {
    $sessions = $pdo->query(
        'SELECT id, started_at, ended_at, notes FROM sessions ORDER BY started_at DESC'
    )->fetchAll();
    $movementStatement = $pdo->prepare(
        'SELECT occurred_at FROM movements WHERE session_id = :session_id ORDER BY occurred_at'
    );

    foreach ($sessions as &$session) {
        $movementStatement->execute(['session_id' => $session['id']]);
        $session = [
            'id' => $session['id'],
            'startedAt' => isoDate($session['started_at']),
            'endedAt' => $session['ended_at'] ? isoDate($session['ended_at']) : null,
            'notes' => $session['notes'] ?? '',
            'movements' => array_map(
                static fn (string $date): string => isoDate($date),
                $movementStatement->fetchAll(PDO::FETCH_COLUMN)
            ),
        ];
    }

    respond(['ok' => true, 'data' => $sessions]);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    respond(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

$session = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($session)) {
    respond(['ok' => false, 'error' => 'Se esperaba una sesión en formato JSON.'], 400);
}

$id = $session['id'] ?? null;
$startedAt = $session['startedAt'] ?? null;
$endedAt = $session['endedAt'] ?? null;
$notes = trim((string) ($session['notes'] ?? ''));
$movements = $session['movements'] ?? null;

if (!is_string($id) || !preg_match('/^[a-zA-Z0-9-]{1,64}$/', $id)) {
    respond(['ok' => false, 'error' => 'El identificador de sesión es inválido.'], 422);
}

if (!is_string($startedAt) || !is_string($endedAt) || !is_array($movements) || $movements === []) {
    respond(['ok' => false, 'error' => 'La sesión está incompleta.'], 422);
}

if (mb_strlen($notes) > 500 || count($movements) > 10000) {
    respond(['ok' => false, 'error' => 'La sesión excede los límites permitidos.'], 422);
}

$startedAt = mysqlDate($startedAt);
$endedAt = mysqlDate($endedAt);
$movements = array_map(
    static function (mixed $movement): string {
        if (!is_string($movement)) {
            respond(['ok' => false, 'error' => 'La sesión contiene un movimiento inválido.'], 422);
        }

        return mysqlDate($movement);
    },
    $movements
);

if ($endedAt < $startedAt) {
    respond(['ok' => false, 'error' => 'La fecha final precede al inicio.'], 422);
}

try {
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'INSERT INTO sessions (id, started_at, ended_at, notes) VALUES (:id, :started_at, :ended_at, :notes)'
    );
    $statement->execute([
        'id' => $id,
        'started_at' => $startedAt,
        'ended_at' => $endedAt,
        'notes' => $notes === '' ? null : $notes,
    ]);

    $movementStatement = $pdo->prepare(
        'INSERT INTO movements (session_id, occurred_at) VALUES (:session_id, :occurred_at)'
    );
    foreach ($movements as $movement) {
        $movementStatement->execute(['session_id' => $id, 'occurred_at' => $movement]);
    }

    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status = $exception->getCode() === '23000' ? 409 : 500;
    $message = $status === 409 ? 'Esta sesión ya fue guardada.' : 'No se pudo guardar la sesión.';
    respond(['ok' => false, 'error' => $message], $status);
}

respond(['ok' => true, 'data' => ['id' => $id]], 201);
