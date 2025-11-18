<?php

/**
 * Script di test connessione database
 * Testa la connessione usando le stesse credenziali di CodeIgniter
 */

// Carica il file .env
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("❌ File .env non trovato in: $envFile\n");
}

echo "📋 Lettura configurazione da .env...\n\n";

// Parse del file .env
$envContent = file_get_contents($envFile);
$lines = explode("\n", $envContent);
$config = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) {
        continue;
    }

    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B'\"");
        $config[$key] = $value;
    }
}

// Estrai credenziali database
$hostname = $config['database.default.hostname'] ?? 'localhost';
$database = $config['database.default.database'] ?? 'qrart';
$username = $config['database.default.username'] ?? 'qrart';
$password = $config['database.default.password'] ?? '';
$port = $config['database.default.port'] ?? 3306;

echo "Configurazione Database:\n";
echo "  Hostname: $hostname\n";
echo "  Database: $database\n";
echo "  Username: $username\n";
echo "  Password: " . str_repeat('*', min(strlen($password), 10)) . "\n";
echo "  Port: $port\n\n";

// Test 1: Verifica se MySQLi è disponibile
echo "🔍 Test 1: Verifica estensione MySQLi...\n";
if (!extension_loaded('mysqli')) {
    die("❌ Estensione MySQLi non installata!\n   Installa con: sudo apt-get install php-mysqli\n");
}
echo "✅ MySQLi disponibile\n\n";

// Test 2: Test connessione
echo "🔍 Test 2: Tentativo di connessione...\n";
$mysqli = @new mysqli($hostname, $username, $password, $database, $port);

if ($mysqli->connect_errno) {
    echo "❌ Connessione FALLITA!\n";
    echo "   Errore: " . $mysqli->connect_error . "\n";
    echo "   Codice errore: " . $mysqli->connect_errno . "\n\n";

    echo "💡 Possibili cause:\n";
    echo "   1. MySQL non è in esecuzione\n";
    echo "      Comando: sudo systemctl status mysql\n\n";
    echo "   2. Credenziali errate\n";
    echo "      Verifica .env e confronta con mysql\n\n";
    echo "   3. Database '$database' non esiste\n";
    echo "      Crea con: CREATE DATABASE $database;\n\n";
    echo "   4. Utente '$username' non ha permessi\n";
    echo "      GRANT ALL ON $database.* TO '$username'@'localhost';\n\n";

    exit(1);
}

echo "✅ Connessione riuscita!\n\n";

// Test 3: Verifica database
echo "🔍 Test 3: Verifica database...\n";
$result = $mysqli->query("SELECT DATABASE() as db_name");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Database selezionato: " . $row['db_name'] . "\n\n";
} else {
    echo "⚠️  Impossibile verificare il database\n\n";
}

// Test 4: Verifica tabelle
echo "🔍 Test 4: Verifica tabelle esistenti...\n";
$result = $mysqli->query("SHOW TABLES");
if ($result) {
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    if (empty($tables)) {
        echo "⚠️  Nessuna tabella trovata nel database!\n";
        echo "   Esegui: php spark migrate\n\n";
    } else {
        echo "✅ Tabelle trovate (" . count($tables) . "):\n";
        foreach ($tables as $table) {
            echo "   - $table\n";
        }
        echo "\n";
    }
}

// Test 5: Verifica tabelle analytics
echo "🔍 Test 5: Verifica tabelle analytics...\n";
$requiredTables = ['analytics_events', 'content_metrics', 'user_sessions', 'content'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo "✅ Tutte le tabelle analytics sono presenti\n\n";
} else {
    echo "⚠️  Tabelle mancanti:\n";
    foreach ($missingTables as $table) {
        echo "   - $table\n";
    }
    echo "\n   Esegui: php spark migrate\n\n";
}

// Test 6: Test query
echo "🔍 Test 6: Test query di prova...\n";
$result = $mysqli->query("SELECT 1 as test");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Query eseguita con successo (risultato: " . $row['test'] . ")\n\n";
} else {
    echo "❌ Errore nell'esecuzione della query\n\n";
}

$mysqli->close();

echo "═══════════════════════════════════════\n";
echo "✅ Tutti i test completati con successo!\n";
echo "═══════════════════════════════════════\n\n";

echo "🚀 Prossimi passi:\n";
if (!empty($missingTables)) {
    echo "  1. Esegui le migrations: php spark migrate\n";
    echo "  2. Ricarica la dashboard analytics\n";
} else {
    echo "  1. La connessione funziona!\n";
    echo "  2. Se la dashboard ancora non funziona, controlla i log:\n";
    echo "     tail -f writable/logs/log-*.log\n";
}
