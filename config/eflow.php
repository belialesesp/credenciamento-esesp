<?php
/**
 * Configuração do e-flow
 * Coloque este arquivo em: /config/eflow.php
 */

return [
    // Chave API para autenticar webhooks do e-flow
    // MESMA chave configurada no .env
    'webhook_api_key' => getenv('EFLOW_WEBHOOK_API_KEY') ?: 'esesp_webhook_2025_a3k9m2l5p8',
    
    // ID do órgão (ESESP) no e-flow (para futuras integrações)
    'orgao_id' => getenv('EFLOW_ORGAO_ID') ?: 123,
    
    // ID do formulário de cadastro de docentes
    'formulario_docente_id' => getenv('EFLOW_FORMULARIO_DOCENTE_ID') ?: 456,
    
    // Categorias mapeadas por ID de formulário (se usar formulários separados)
    'categorias_por_formulario' => [
        // Descomente e configure se usar formulários diferentes para cada categoria
        // 123 => 'docente',
        // 124 => 'docente_pos',
        // 125 => 'tecnico',
        // 126 => 'interprete'
    ],
];