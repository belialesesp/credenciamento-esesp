<?php
// process/process_registration.php

session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================
// DOCUMENT MAPPING CONFIGURATION
// ==========================================

$documentMapping = [
    // Personal Documents (Common)
    'comprovante_residencia' => [
        'type' => 'personal',
        'id' => 1,
        'required' => true,
        'name' => 'Comprovante de Residência'
    ],
    'documento_identificacao' => [
        'type' => 'personal',
        'id' => 2,
        'required' => true,
        'name' => 'Documento de Identificação'
    ],
    'titulo_eleitor' => [
        'type' => 'personal',
        'id' => 3,
        'required' => true,
        'name' => 'Título de Eleitor'
    ],
    'certificado_reservista' => [
        'type' => 'personal',
        'id' => 4,
        'required' => false,
        'name' => 'Certificado de Reservista'
    ],
    'pis_pasep' => [
        'type' => 'personal',
        'id' => 5,
        'required' => true,
        'name' => 'PIS/PASEP'
    ],
    'protocolo_siades' => [
        'type' => 'personal',
        'id' => 6,
        'required' => true,
        'name' => 'Protocolo SIADES'
    ],

    // UNIFIED Professional Qualification Documents
    // These now work for Docente, Docente-Pos, AND Técnico
    'formacao_escolar' => [
        'type' => 'qualification',
        'id' => 7,
        'required' => false, // Will be required based on role selection
        'name' => 'Formação Escolar',
        'roles' => ['docente', 'docente-pos', 'tecnico'] // Now includes tecnico
    ],
    'experiencia_profissional' => [
        'type' => 'qualification',
        'id' => 8,
        'required' => false,
        'name' => 'Experiência Profissional',
        'roles' => ['docente', 'docente-pos', 'tecnico'] // Now includes tecnico
    ],

    // Optional documents for Docente roles only
    'publicacoes' => [
        'type' => 'qualification',
        'id' => 9,
        'required' => false,
        'name' => 'Publicações',
        'roles' => ['docente', 'docente-pos']
    ],
    'certificados_cursos' => [
        'type' => 'qualification',
        'id' => 10,
        'required' => false,
        'name' => 'Certificados de Cursos',
        'roles' => ['docente', 'docente-pos']
    ],

    // Intérprete-specific Documents (unchanged)
    'certificacao_libras' => [
        'type' => 'qualification',
        'id' => 11,
        'required' => false,
        'name' => 'Certificação em Libras',
        'roles' => ['interprete']
    ],
    'experiencia_libras' => [
        'type' => 'qualification',
        'id' => 12,
        'required' => false,
        'name' => 'Experiência em Libras',
        'roles' => ['interprete']
    ],
    'certificado_prolibras' => [
        'type' => 'qualification',
        'id' => 13,
        'required' => false,
        'name' => 'Certificado Prolibras',
        'roles' => ['interprete']
    ],

    // Financial Documents (unchanged)
    'certidao_federal' => [
        'type' => 'financial',
        'id' => 14,
        'required' => true,
        'name' => 'Certidão Federal'
    ],
    'certidao_trabalhista' => [
        'type' => 'financial',
        'id' => 15,
        'required' => true,
        'name' => 'Certidão Trabalhista'
    ],
    'certidao_estadual' => [
        'type' => 'financial',
        'id' => 16,
        'required' => true,
        'name' => 'Certidão Estadual'
    ],
    'certidao_municipal' => [
        'type' => 'financial',
        'id' => 17,
        'required' => true,
        'name' => 'Certidão Municipal'
    ],
    'certidao_fgts' => [
        'type' => 'financial',
        'id' => 18,
        'required' => true,
        'name' => 'Certidão FGTS'
    ],
    'certidao_conjunta' => [
        'type' => 'financial',
        'id' => 19,
        'required' => true,
        'name' => 'Certidão Conjunta PGFN e RFB'
    ]
];

// ==========================================
// MAIN PROCESSING LOGIC
// ==========================================

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Método não permitido');
}

try {
    // Start database transaction
    $pdo->beginTransaction();

    // Get selected roles
    $selectedRoles = $_POST['roles'] ?? [];
    if (empty($selectedRoles)) {
        throw new Exception('Nenhuma função selecionada');
    }

    // Validate CPF
    $cpf = $_POST['cpf'] ?? '';
    if (!validateCPF($cpf)) {
        throw new Exception('CPF inválido');
    }

    // Clean CPF for database storage
    $cpfClean = preg_replace('/\D/', '', $cpf);

    // Check if user exists - FIXED: Now selecting name field too
    $stmt = $pdo->prepare("SELECT id, name FROM user WHERE cpf = :cpf");
    $stmt->execute([':cpf' => $cpfClean]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Clear any output
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send direct JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'CPF já cadastrado no sistema. Redirecionando para login...',
            'already_registered' => true,
            'redirect_url' => '../pages/login.php',
            'user_name' => isset($existingUser['name']) ? $existingUser['name'] : 'usuário'
        ]);
        exit();
    }

    // Process special needs
    $specialNeeds = (isset($_POST['special_needs']) && $_POST['special_needs'] === 'yes') ? 'Sim' : 'Não';

    // Process bank account details
    $bankCode = isset($_POST['codigo_banco']) ? $_POST['codigo_banco'] : null;
    $bankName = isset($_POST['nome_banco']) ? $_POST['nome_banco'] : null;
    $agency = isset($_POST['agencia']) ? $_POST['agencia'] : null;
    $account = isset($_POST['conta']) ? $_POST['conta'] : null;
    $conta_bancaria = "Banco: $bankName ($bankCode), Agência: $agency, Conta: $account";

    // Prepare user data with proper isset checks
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';

    // Hash the password (using CPF as default password)
    $passwordHash = password_hash($cpfClean, PASSWORD_DEFAULT);

    // Create new user
    $stmt = $pdo->prepare("
    INSERT INTO user (
        cpf, document_number, document_emissor, document_uf, name, email, phone, 
        street, number, complement,
        neighborhood, city, state, zip_code,
        scholarship, special_needs, 
        conta_bancaria, 
        password_hash, created_at
    ) VALUES (
        :cpf, :document_number, :document_emissor, :document_uf, :name, :email, :phone,
        :street, :number, :complement,
        :neighborhood, :city, :state, :zip_code,
        :scholarship, :special_needs,
        :conta_bancaria,
        :password_hash, NOW()
    )
");

    $insertData = [
        ':cpf' => $cpfClean,
        ':document_number' => $_POST['document_number'] ?? null,
        ':document_emissor' => $_POST['document_emissor'] ?? null,
        ':document_uf' => $_POST['document_uf'] ?? null,
        ':name' => $_POST['name'] ?? null,
        ':email' => $_POST['email'] ?? null,
        ':phone' => $_POST['phone'] ?? null,
        ':street' => $_POST['street'] ?? null,
        ':number' => $_POST['number'] ?? null,
        ':complement' => $_POST['complement'] ?? null,
        ':neighborhood' => $_POST['neighborhood'] ?? null,
        ':city' => $_POST['city'] ?? null,
        ':state' => $_POST['state'] ?? null,
        ':zip_code' => $_POST['zipCode'] ?? null,
        ':scholarship' => $_POST['scholarship'] ?? null,
        ':special_needs' => $specialNeeds,
        ':conta_bancaria' => $conta_bancaria,
        ':password_hash' => $passwordHash
    ];

    $stmt->execute($insertData);
    $userId = $pdo->lastInsertId();


    // Process education data
    if (isset($_POST['course_name']) && is_array($_POST['course_name'])) {
        // Delete existing education records
        $stmt = $pdo->prepare("DELETE FROM education_degree WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);

        // Insert new education records
        $stmt = $pdo->prepare("
            INSERT INTO education_degree (user_id, course_name, degree, institution, created_at)
            VALUES (:user_id, :course_name, :degree, :institution, NOW())
        ");

        for ($i = 0; $i < count($_POST['course_name']); $i++) {
            if (!empty($_POST['course_name'][$i]) && !empty($_POST['institution'][$i])) {
                $stmt->execute([
                    ':user_id' => $userId,
                    ':course_name' => $_POST['course_name'][$i],
                    ':degree' => $_POST['degree'][$i],
                    ':institution' => $_POST['institution'][$i]
                ]);
            }
        }
    }

    // Process role-specific data
    $roleIds = processRoleSpecificData($pdo, $userId, $selectedRoles, $_POST);

    // Process uploaded documents
    $uploadedCount = 0;
    $failedUploads = [];
    $documentIds = [];

    foreach ($_FILES as $fieldName => $file) {
        // Skip if no file uploaded
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        // Check if field is in our mapping
        if (!isset($documentMapping[$fieldName])) {
            continue;
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $failedUploads[] = $fieldName;
            continue;
        }

        try {
            // Get document info from mapping
            $docInfo = $documentMapping[$fieldName];

            // Check if document is required for selected roles
            if (isset($docInfo['roles'])) {
                $hasRequiredRole = false;
                foreach ($docInfo['roles'] as $requiredRole) {
                    if (in_array($requiredRole, $selectedRoles)) {
                        $hasRequiredRole = true;
                        break;
                    }
                }
                if (!$hasRequiredRole) {
                    continue; // Skip this document if role not selected
                }
            }

            // Upload the file with decentralized structure
            $uploadResult = uploadDocument($file, $userId, $docInfo, $selectedRoles);

            // Save to database
            $documentId = saveDocumentRecord($pdo, $userId, $docInfo, $uploadResult);
            $documentIds[] = $documentId;

            $uploadedCount++;
        } catch (Exception $e) {
            error_log("Failed to upload {$fieldName}: " . $e->getMessage());
            $failedUploads[] = $fieldName;
        }
    }


    // Commit transaction
    $pdo->commit();

    // Auto-login the newly registered user
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['first_login'] = true;
    $_SESSION['user_roles'] = $selectedRoles;
    $_SESSION['is_admin'] = in_array('admin', $selectedRoles);

    // Determine primary role for redirect
    $primaryRole = null;
    $priorityRoles = ['admin', 'docente-pos', 'docente', 'tecnico', 'interprete'];

    foreach ($priorityRoles as $role) {
        if (in_array($role, $selectedRoles)) {
            $primaryRole = $role;
            break;
        }
    }

    $_SESSION['user_type'] = $primaryRole;
    $_SESSION['type_id'] = $userId;

    // Determine redirect URL based on primary role
    $redirectUrl = '../pages/home.php'; // Default

    switch ($primaryRole) {
        case 'docente':
            $redirectUrl = '../pages/docente.php?id=' . $userId . '&first_login=true';
            break;
        case 'docente-pos':
            $redirectUrl = '../pages/docente-pos.php?id=' . $userId . '&first_login=true';
            break;
        case 'tecnico':
            $redirectUrl = '../pages/tecnico.php?id=' . $userId . '&first_login=true';
            break;
        case 'interprete':
            $redirectUrl = '../pages/interprete.php?id=' . $userId . '&first_login=true';
            break;
        case 'admin':
            $redirectUrl = '../pages/home.php';
            break;
    }

    // Prepare response message
    $message = 'Cadastro realizado com sucesso!';
    if ($uploadedCount > 0) {
        $message .= " {$uploadedCount} documento(s) enviado(s).";
    }
    if (count($failedUploads) > 0) {
        $message .= " Alguns documentos falharam no envio.";
    }

    // Send response with redirect URL
    sendResponse(true, $message, [
        'user_id' => $userId,
        'documents_uploaded' => $uploadedCount,
        'failed_uploads' => $failedUploads,
        'roles' => $selectedRoles,
        'redirect_url' => $redirectUrl
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }

    error_log("Registration error: " . $e->getMessage());
    sendResponse(false, 'Erro ao processar cadastro: ' . $e->getMessage());
}
// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Send JSON response and exit
 */
function sendResponse($success, $message, $data = [])
{
    header('Content-Type: ' . ($success ? 'application/json' : 'text/plain'));
    if ($success) {
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $data));
    } else {
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }
    exit;
}

/**
 * Validate CPF format and digits
 */
function validateCPF($cpf)
{
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) != 11) {
        return false;
    }

    // Check for known invalid CPFs
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Validate check digits
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}

$cpf = $_POST['cpf'] ?? '';
if (!validateCPF($cpf)) {
    throw new Exception('CPF inválido');
}

// Clean CPF for database storage and set as initial password
$cpfClean = preg_replace('/\D/', '', $cpf);
$passwordHash = password_hash($cpfClean, PASSWORD_DEFAULT);

/**
 * Upload a document file with decentralized structure
 */
function uploadDocument($file, $userId, $docInfo, $roles)
{
    // Define decentralized upload directory structure based on role and document type
    $baseUploadDir = '../uploads/';

    // Determine primary role for folder structure
    $primaryRole = 'common';
    if (in_array('docente', $roles)) {
        $primaryRole = 'docentes';
    } elseif (in_array('docente-pos', $roles)) {
        $primaryRole = 'docentes_pos';
    } elseif (in_array('interprete', $roles)) {
        $primaryRole = 'interpretes';
    } elseif (in_array('tecnico', $roles)) {
        $primaryRole = 'tecnicos';
    }

    // Create folder structure: uploads/[role]/[document_type]/[user_id]/
    $documentTypeFolder = strtolower(str_replace(' ', '_', $docInfo['type']));
    $userDir = $baseUploadDir . $primaryRole . '/' . $documentTypeFolder . '/' . $userId . '/';

    // Create directories if they don't exist
    if (!file_exists($userDir)) {
        mkdir($userDir, 0755, true);
    }

    // Validate file type
    $allowedTypes = ['application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("Arquivo inválido. Apenas PDFs são aceitos.");
    }

    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception("Arquivo muito grande. Máximo permitido: 10MB");
    }

    // Generate unique filename with metadata
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $sanitizedDocName = preg_replace('/[^a-z0-9_]/', '_', strtolower($docInfo['name']));
    $timestamp = date('YmdHis');
    $randomString = bin2hex(random_bytes(4));
    $fileName = $sanitizedDocName . '_' . $timestamp . '_' . $randomString . '.' . $fileExtension;
    $filePath = $userDir . $fileName;

    // Calculate file hash for integrity check
    $fileHash = hash_file('sha256', $file['tmp_name']);

    // Store relative path for database
    $relativePath = 'uploads/' . $primaryRole . '/' . $documentTypeFolder . '/' . $userId . '/' . $fileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Erro ao salvar o arquivo.");
    }

    return [
        'path' => $relativePath,
        'name' => $file['name'],
        'size' => $file['size'],
        'mime_type' => $mimeType,
        'file_hash' => $fileHash,
        'role_folder' => $primaryRole
    ];
}

/**
 * Save document record to database with new structure
 */
function saveDocumentRecord($pdo, $userId, $docInfo, $uploadResult)
{
    try {
        // Check if document already exists for this user and type
        $stmt = $pdo->prepare("
            SELECT id FROM documents 
            WHERE user_id = :user_id 
            AND document_type_id = :document_type_id
            LIMIT 1
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':document_type_id' => $docInfo['id']
        ]);

        $existingDoc = $stmt->fetch();

        if ($existingDoc) {
            // Update existing document (replace old version)
            $stmt = $pdo->prepare("
                UPDATE documents SET
                    name = :name,
                    original_name = :original_name,
                    path = :path,
                    file_size = :file_size,
                    mime_type = :mime_type,
                    file_hash = :file_hash,
                    upload_status = 'uploaded',
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $existingDoc['id'],
                ':name' => $docInfo['name'],
                ':original_name' => $uploadResult['name'],
                ':path' => $uploadResult['path'],
                ':file_size' => $uploadResult['size'],
                ':mime_type' => $uploadResult['mime_type'],
                ':file_hash' => $uploadResult['file_hash']
            ]);

            return $existingDoc['id'];
        } else {
            // Insert into documents table (main document storage)
            $stmt = $pdo->prepare("
                INSERT INTO documents (
                    user_id, 
                    document_type_id,
                    name,
                    original_name,
                    path,
                    file_size,
                    mime_type,
                    file_hash,
                    upload_status,
                    created_at
                ) VALUES (
                    :user_id,
                    :document_type_id,
                    :name,
                    :original_name,
                    :path,
                    :file_size,
                    :mime_type,
                    :file_hash,
                    'uploaded',
                    NOW()
                )
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':document_type_id' => $docInfo['id'],
                ':name' => $docInfo['name'],
                ':original_name' => $uploadResult['name'],
                ':path' => $uploadResult['path'],
                ':file_size' => $uploadResult['size'],
                ':mime_type' => $uploadResult['mime_type'],
                ':file_hash' => $uploadResult['file_hash']
            ]);

            return $pdo->lastInsertId();
        }
    } catch (Exception $e) {
        error_log("Error saving document: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Process role-specific data
 */
function processRoleSpecificData($pdo, $userId, $roles, $postData)
{
    $roleIds = [];

    // Map roles to professional categories
    $roleMapping = [
        'docente' => 1,
        'docente-pos' => 2,
        'interprete' => 3,
        'tecnico' => 4
    ];

    foreach ($roles as $role) {
        if (isset($roleMapping[$role])) {
            $roleIds[] = $roleMapping[$role];

            // Handle role-specific tables
            switch ($role) {
                case 'docente':
                    processDocenteData($pdo, $userId, $postData);
                    break;
                case 'docente-pos':
                    processDocentePosData($pdo, $userId, $postData);
                    break;
            }
        }
    }

    // Store user roles
    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);

    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role, created_at)
        VALUES (:user_id, :role, NOW())
    ");

    foreach ($roleIds as $roleId) {
        $stmt->execute([
            ':user_id' => $userId,
            ':role' => $roleId
        ]);
    }

    return $roleIds;
}

function processDocenteData($pdo, $userId, $postData)
{
    // Process docente categories
    if (isset($postData['docente_categories']) && is_array($postData['docente_categories'])) {
        $stmt = $pdo->prepare("
            INSERT INTO teacher_activities (user_id, activity_id, created_at)
            VALUES (:user_id, :activity_id, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");

        foreach ($postData['docente_categories'] as $categoryId) {
            $stmt->execute([
                ':user_id' => $userId,
                ':activity_id' => intval($categoryId)
            ]);
        }
    }
}

function processDocentePosData($pdo, $userId, $postData)
{
    // Process docente pos categories
    if (isset($postData['docente_pos_categories']) && is_array($postData['docente_pos_categories'])) {
        $stmt = $pdo->prepare("
            INSERT INTO postg_teacher_activities (user_id, activity_id, created_at)
            VALUES (:user_id, :activity_id, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");

        foreach ($postData['docente_pos_categories'] as $categoryId) {
            $stmt->execute([
                ':user_id' => $userId,
                ':activity_id' => intval($categoryId)
            ]);
        }
    }
}
