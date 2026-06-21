<?php
/**
 * ViceHub X — Connexion PDO (singleton).
 */

declare(strict_types=1);

/**
 * Retourne une instance PDO partagée.
 * Utilise des requêtes préparées et le mode exception.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $hint = (getenv('APP_ENV') ?: 'dev') === 'prod'
            ? 'Service momentanément indisponible.'
            : 'Connexion base de données impossible : ' . $e->getMessage()
              . ' — vérifiez config/config.php et que la base "' . DB_NAME . '" existe.';
        exit('<p style="font-family:sans-serif;padding:2rem;color:#fff;background:#0a0a12">'
            . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '</p>');
    }

    return $pdo;
}
