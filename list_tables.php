<?php
try {
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'ecommerce.db';
    if (!file_exists($path)) {
        echo "ERROR: ecommerce.db not found at $path\n";
        exit(2);
    }
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT type, name FROM sqlite_master WHERE type IN ('table','view') ORDER BY name;");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "No tables or views found\n";
        exit(0);
    }
    foreach ($rows as $r) {
        echo $r['type'] . ' : ' . $r['name'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
