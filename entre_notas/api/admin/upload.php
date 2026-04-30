<?php
ob_start();

try {
    require_once __DIR__ . '/auth.php';
} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro de autenticação: ' . $e->getMessage()]);
    exit();
}

ob_end_clean();
header('Content-Type: application/json');

define('CLD_CLOUD',  'dezucm5c4');
define('CLD_KEY',    '291555826468233');
define('CLD_SECRET', 'pBOXAMTETQcftFIXahSz0taOwL4');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'Nenhum arquivo enviado']);
    exit();
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Erro no upload: código ' . $file['error']]);
    exit();
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Arquivo muito grande. Máximo 5MB.']);
    exit();
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} else {
    $extMap   = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeType = $extMap[$ext] ?? 'application/octet-stream';
}

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Tipo não permitido. Use JPG, PNG, WEBP ou GIF.']);
    exit();
}

// Enviar para Cloudinary via API REST
$timestamp = time();
$signature = sha1('timestamp=' . $timestamp . CLD_SECRET);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => 'https://api.cloudinary.com/v1_1/' . CLD_CLOUD . '/image/upload',
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'file'      => new CURLFile($file['tmp_name'], $mimeType, $file['name']),
        'api_key'   => CLD_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão com Cloudinary: ' . $curlError]);
    exit();
}

$data = json_decode($response, true);

if (isset($data['secure_url'])) {
    echo json_encode(['success' => true, 'url' => $data['secure_url'], 'message' => 'Upload realizado com sucesso!']);
} else {
    $msg = $data['error']['message'] ?? 'Resposta inesperada do Cloudinary';
    echo json_encode(['success' => false, 'error' => $msg]);
}
