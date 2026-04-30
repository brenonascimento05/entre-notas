<?php
$host = 'localhost';
$dbname = 'entre_notas_db';
$usuario = 'root';
$senha = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die(json_encode(['error' => 'Erro no banco: ' . $e->getMessage()]));
}
?>