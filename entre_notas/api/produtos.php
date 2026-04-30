<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/db.php';

try {
    // Buscar todos os perfumes
    $sql = "SELECT * FROM perfumes ORDER BY id_perfume DESC";
    $stmt = $pdo->query($sql);
    $perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $produtos = [];
    
    foreach ($perfumes as $perfume) {
        // Buscar variantes
        $stmtVar = $pdo->prepare("SELECT * FROM variantes WHERE id_perfume = ?");
        $stmtVar->execute([$perfume['id_perfume']]);
        $variantes = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar imagens
        $stmtImg = $pdo->prepare("SELECT url_imagem FROM perfume_imagens WHERE id_perfume = ? ORDER BY ordem");
        $stmtImg->execute([$perfume['id_perfume']]);
        $imagens = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
        
        // Se não houver imagens na galeria, usar a principal
        if (empty($imagens)) {
            $imagens = [$perfume['imagem_principal']];
        }
        
        // Calcular média de avaliações (mock para teste)
        $media_avaliacoes = 4.5;
        $total_avaliacoes = rand(100, 5000);
        
        $produtos[] = [
            'id' => $perfume['id_perfume'],
            'nome' => $perfume['nome'],
            'marca' => $perfume['marca'],
            'descricao' => $perfume['descricao'] ?? '',
            'imagem_principal' => $perfume['imagem_principal'] ?? 'https://placehold.co/400x300?text=Perfume',
            'imagens' => $imagens,
            'destaque' => (bool)$perfume['destaque'],
            'media_avaliacoes' => $media_avaliacoes,
            'total_avaliacoes' => $total_avaliacoes,
            'variantes' => array_map(function($v) {
                return [
                    'id_variante' => $v['id_variante'],
                    'tamanho_ml' => $v['tamanho_ml'],
                    'preco' => (float)$v['preco'],
                    'preco_original' => $v['preco_original'] ? (float)$v['preco_original'] : null,
                    'estoque' => $v['estoque'],
                    'preco_por_ml' => round($v['preco'] / $v['tamanho_ml'], 2)
                ];
            }, $variantes),
            'vendas' => rand(100, 2000),
            'aroma' => $perfume['notas_olfativas'] ? explode(',', $perfume['notas_olfativas'])[0] : 'amadeirado'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'source' => 'database',
        'total' => count($produtos),
        'data' => $produtos,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>