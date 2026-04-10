<?php

declare(strict_types=1);

// Simple migration runner
// Run with: php database/migrate.php

$host     = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: 'postgres';
$port     = $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?: '5432';
$name     = $_ENV['DB_NAME']     ?? getenv('DB_NAME')     ?: 'fulcrum';
$user     = $_ENV['DB_USER']     ?? getenv('DB_USER')     ?: 'fulcrum';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'fulcrum';

try {
    $pdo = new PDO(
        dsn:      "pgsql:host={$host};port={$port};dbname={$name}",
        username: $user,
        password: $password,
        options:  [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Create migrations table if it doesn't exist yet
// This is the only hardcoded SQL in the runner
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id       SERIAL PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        ran_at   TIMESTAMP NOT NULL DEFAULT NOW()
    )
");

// Find migration files
$migrationsPath = __DIR__ . '/migrations';
$files          = glob($migrationsPath . '/*.sql');

if (empty($files)) {
    echo "No migration files found." . PHP_EOL;
    exit(0);
}

sort($files);

// Find already ran migrations
$ran = $pdo
    ->query("SELECT filename FROM migrations ORDER BY filename")
    ->fetchAll(PDO::FETCH_COLUMN);

$ran = array_flip($ran);

$count = 0;

foreach ($files as $file) {
    $filename = basename($file);

    // Skip already ran migrations
    if (isset($ran[$filename])) {
        echo "  skip  {$filename}" . PHP_EOL;
        continue;
    }

    // Run the migration
    $sql = file_get_contents($file);

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)")
            ->execute([$filename]);
        $pdo->commit();

        echo "  ran   {$filename}" . PHP_EOL;
        $count++;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "  FAIL  {$filename}: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo PHP_EOL . ($count === 0
    ? "Nothing to migrate."
    : "Migrated {$count} file(s)."
) . PHP_EOL;