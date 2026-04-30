<?php
require_once __DIR__ . '/../db.php';

$username = 'admin';
$newPassword = 'admin123';

// Gerar hash da senha
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "<h2>🔐 Configurando senha do administrador</h2>";

// Verificar se a tabela existe
$stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
if ($stmt->rowCount() == 0) {
    // Criar tabela
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        nome VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Tabela admin_users criada<br>";
}

// Atualizar ou criar usuário
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);
    echo "✅ Senha do usuário '{$username}' atualizada com sucesso!<br>";
} else {
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, nome) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hash, 'Administrador']);
    echo "✅ Usuário '{$username}' criado com sucesso!<br>";
}

echo "<br><strong>🔐 Credenciais:</strong><br>";
echo "Usuário: <strong style='color:#d4af37'>{$username}</strong><br>";
echo "Senha: <strong style='color:#d4af37'>{$newPassword}</strong><br>";
echo "<br><a href='../admin/login.html' style='background:#d4af37; padding:10px 20px; text-decoration:none; color:#1a1a2e; border-radius:30px;'>🔑 Ir para o login →</a>";
?>