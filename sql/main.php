<?php
// pdo with sqlite
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
$pdo->exec("INSERT INTO test (name) VALUES ('Hello')");

// query
$stmt = $pdo->query('SELECT * FROM test');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
