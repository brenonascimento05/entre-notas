<?php
require_once __DIR__ . '/auth.php';

$pdo = $GLOBALS['pdo'];

// ========== LISTAR produtos ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id'])) {
    try {
        $stmt = $pdo->query("SELECT * FROM perfumes ORDER BY id_perfume DESC");
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $produtos]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ========== BUSCAR um produto ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM perfumes WHERE id_perfume = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($produto) {
            $stmtVar = $pdo->prepare("SELECT * FROM variantes WHERE id_perfume = ?");
            $stmtVar->execute([$id]);
            $produto['variantes'] = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
            
            $stmtImg = $pdo->prepare("SELECT url_imagem FROM perfume_imagens WHERE id_perfume = ? ORDER BY ordem");
            $stmtImg->execute([$id]);
            $produto['imagens'] = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
        }
        
        echo json_encode(['success' => true, 'data' => $produto]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ========== CRIAR produto ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO perfumes (nome, marca, descricao, imagem_principal, destaque) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['nome'],
            $data['marca'],
            $data['descricao'] ?? '',
            $data['imagem_principal'] ?? '',
            isset($data['destaque']) && $data['destaque'] ? 1 : 0
        ]);
        $perfumeId = $pdo->lastInsertId();
        
        if (isset($data['variantes']) && is_array($data['variantes'])) {
            foreach ($data['variantes'] as $v) {
                if (!empty($v['ml']) && !empty($v['preco'])) {
                    $stmt = $pdo->prepare("INSERT INTO variantes (id_perfume, tamanho_ml, preco, preco_original, estoque) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$perfumeId, $v['ml'], $v['preco'], $v['preco_original'] ?? null, $v['estoque'] ?? 0]);
                }
            }
        }
        
        if (isset($data['imagens']) && is_array($data['imagens'])) {
            foreach ($data['imagens'] as $idx => $img) {
                if (!empty($img)) {
                    $stmt = $pdo->prepare("INSERT INTO perfume_imagens (id_perfume, url_imagem, ordem) VALUES (?, ?, ?)");
                    $stmt->execute([$perfumeId, $img, $idx]);
                }
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Produto criado com sucesso', 'id' => $perfumeId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ========== ATUALIZAR produto ==========
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE perfumes SET nome = ?, marca = ?, descricao = ?, imagem_principal = ?, destaque = ? WHERE id_perfume = ?");
        $stmt->execute([$data['nome'], $data['marca'], $data['descricao'] ?? '', $data['imagem_principal'] ?? '', isset($data['destaque']) && $data['destaque'] ? 1 : 0, $data['id']]);
        
        $stmt = $pdo->prepare("DELETE FROM variantes WHERE id_perfume = ?");
        $stmt->execute([$data['id']]);
        
        if (isset($data['variantes']) && is_array($data['variantes'])) {
            foreach ($data['variantes'] as $v) {
                if (!empty($v['ml']) && !empty($v['preco'])) {
                    $stmt = $pdo->prepare("INSERT INTO variantes (id_perfume, tamanho_ml, preco, preco_original, estoque) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$data['id'], $v['ml'], $v['preco'], $v['preco_original'] ?? null, $v['estoque'] ?? 0]);
                }
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM perfume_imagens WHERE id_perfume = ?");
        $stmt->execute([$data['id']]);
        
        if (isset($data['imagens']) && is_array($data['imagens'])) {
            foreach ($data['imagens'] as $idx => $img) {
                if (!empty($img)) {
                    $stmt = $pdo->prepare("INSERT INTO perfume_imagens (id_perfume, url_imagem, ordem) VALUES (?, ?, ?)");
                    $stmt->execute([$data['id'], $img, $idx]);
                }
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Produto atualizado com sucesso']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ========== DELETAR produto ==========
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID não informado']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM perfumes WHERE id_perfume = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Produto deletado com sucesso']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?>