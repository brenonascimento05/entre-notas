<?php
// ==================================================
// API: BUSCAR UM PERFUME ESPECÍFICO POR ID
// Exemplo de uso: perfume.php?id=1
// ==================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

// Verificar se o ID foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID do perfume não informado ou inválido'
    ]);
    exit;
}

$id = (int)$_GET['id'];

try {
    // Buscar dados do perfume
    $sql = "SELECT 
                p.*,
                (SELECT AVG(nota) FROM avaliacoes WHERE id_perfume = p.id_perfume) as media_avaliacoes,
                (SELECT COUNT(*) FROM avaliacoes WHERE id_perfume = p.id_perfume) as total_avaliacoes
            FROM perfumes p 
            WHERE p.id_perfume = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $perfume = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$perfume) {
        echo json_encode([
            'success' => false,
            'error' => 'Perfume não encontrado'
        ]);
        exit;
    }
    
    // Buscar variantes do perfume
    $sqlVariantes = "SELECT * FROM variantes WHERE id_perfume = ? ORDER BY tamanho_ml";
    $stmtVariantes = $pdo->prepare($sqlVariantes);
    $stmtVariantes->execute([$id]);
    $variantes = $stmtVariantes->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar imagens da galeria
    $sqlImagens = "SELECT url_imagem FROM perfume_imagens WHERE id_perfume = ? ORDER BY ordem";
    $stmtImagens = $pdo->prepare($sqlImagens);
    $stmtImagens->execute([$id]);
    $imagens = $stmtImagens->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($imagens)) {
        $imagens = [$perfume['imagem_principal']];
    }
    
    // Buscar avaliações
    $sqlAvaliacoes = "SELECT a.*, c.nome as cliente_nome 
                      FROM avaliacoes a
                      JOIN clientes c ON a.id_cliente = c.id_cliente
                      WHERE a.id_perfume = ?
                      ORDER BY a.data_avaliacao DESC
                      LIMIT 10";
    $stmtAvaliacoes = $pdo->prepare($sqlAvaliacoes);
    $stmtAvaliacoes->execute([$id]);
    $avaliacoes = $stmtAvaliacoes->fetchAll(PDO::FETCH_ASSOC);
    
    // Montar resposta
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $perfume['id_perfume'],
            'nome' => $perfume['nome'],
            'marca' => $perfume['marca'],
            'descricao' => $perfume['descricao'],
            'notas_olfativas' => $perfume['notas_olfativas'],
            'imagem_principal' => $perfume['imagem_principal'],
            'imagens' => $imagens,
            'destaque' => (bool)$perfume['destaque'],
            'media_avaliacoes' => round($perfume['media_avaliacoes'] ?? 4.5, 1),
            'total_avaliacoes' => (int)($perfume['total_avaliacoes'] ?? 0),
            'variantes' => array_map(function($v) {
                return [
                    'id_variante' => $v['id_variante'],
                    'tamanho_ml' => $v['tamanho_ml'],
                    'preco' => (float)$v['preco'],
                    'preco_original' => $v['preco_original'] ? (float)$v['preco_original'] : null,
                    'estoque' => $v['estoque'],
                    'desconto' => $v['preco_original'] ? round((1 - $v['preco']/$v['preco_original']) * 100) : 0,
                    'preco_por_ml' => round($v['preco'] / $v['tamanho_ml'], 2)
                ];
            }, $variantes),
            'avaliacoes' => $avaliacoes
        ]
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>