<?php
/**
 * File: /process/process_registration.php
 * 
 * This file handles all registration form submissions from cadastros.php
 * It processes both common and category-specific documents using simple field names
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// ==========================================
// DOCUMENT MAPPING CONFIGURATION
// ==========================================
// Maps HTML form field names to database document type IDs
$documentMapping = [
    // Common documents (from common_document_types table)
    'comprovante_residencia' => [
        'type' => 'common', 
        'id' => 1,
        'required' => true
    ],
    'documento_identificacao' => [
        'type' => 'common', 
        'id' => 2,
        'required' => true
    ],
    'titulo_eleitor' => [
        'type' => 'common', 
        'id' => 3,
        'required' => true
    ],
    'certificado_reservista' => [
        'type' => 'common', 
        'id' => 4,
        'required' => false  // Optional for males only
    ],
    'formacao_escolar' => [
        'type' => 'common', 
        'id' => 6,
        'required' => true
    ],
    'pis_pasep' => [
        'type' => 'common', 
        'id' => 8,
        'required' => true
    ],
    'protocolo_siades' => [
        'type' => 'common', 
        'id' => 9,
        'required' => true
    ],
    
    // Category-specific documents - Docente (Professional Category 1)
    'experiencia_profissional' => [
        'type' => 'category', 
        'id' => 2,
        'category' => 1,
        'required' => true
    ],
    'publicacoes' => [
        'type' => 'category', 
        'id' => 5,
        'category' => 1,
        'required' => false
    ],
    'certificados_cursos' => [
        'type' => 'category', 
        'id' => 4,
        'category' => 1,
        'required' => false
    ],
    
    // Single unified document field (used by some tabs)
    'documents' => [
        'type' => 'unified',
        'id' => 0,  // Will be handled specially
        'required' => true
    ]
];

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Send JSON response and exit
 */
function sendResponse($success, $message, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

/**
 * Validate CPF format and digits
 */
function validateCPF($cpf) {
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

/**
 * Upload a document file
 */
function uploadDocument($file, $userId, $docInfo) {
    // Define upload directory structure
    $baseUploadDir = '../uploads/';
    $documentDir = $baseUploadDir . 'documents/';
    $userDir = $documentDir . $userId . '/';
    
    // Create directories if they don't exist
    if (!file_exists($baseUploadDir)) {
        mkdir($baseUploadDir, 0755, true);
    }
    if (!file_exists($documentDir)) {
        mkdir($documentDir, 0755, true);
    }
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
    
    // Generate unique filename
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $prefix = $docInfo['type'] . '_' . $docInfo['id'];
    $timestamp = date('YmdHis');
    $randomString = bin2hex(random_bytes(4));
    $fileName = $prefix . '_' . $timestamp . '_' . $randomString . '.' . $fileExtension;
    $filePath = $userDir . $fileName;
    
    // Store relative path for database
    $relativePath = 'uploads/documents/' . $userId . '/' . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Erro ao salvar o arquivo.");
    }
    
    return [
        'path' => $relativePath,
        'name' => $file['name'],
        'size' => $file['size'],
        'mime_type' => $mimeType
    ];
}

/**
 * Process and save document to database
 */
function saveDocumentRecord($pdo, $userId, $docInfo, $uploadResult) {
    // Check if document already exists for this user and type
    $stmt = $pdo->prepare("
        SELECT id FROM user_documents 
        WHERE user_id = :user_id 
        AND document_type = :document_type 
        AND document_type_id = :document_type_id
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':document_type' => $docInfo['type'],
        ':document_type_id' => $docInfo['id']
    ]);
    
    $existingDoc = $stmt->fetch();
    
    if ($existingDoc) {
        // Update existing document
        $stmt = $pdo->prepare("
            UPDATE user_documents SET
                file_path = :file_path,
                file_name = :file_name,
                file_size = :file_size,
                uploaded_at = NOW(),
                verification_status = 'pending'
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $existingDoc['id'],
            ':file_path' => $uploadResult['path'],
            ':file_name' => $uploadResult['name'],
            ':file_size' => $uploadResult['size']
        ]);
    } else {
        // Insert new document
        $stmt = $pdo->prepare("
            INSERT INTO user_documents (
                user_id, document_type, document_type_id,
                file_path, file_name, file_size,
                uploaded_at, verification_status
            ) VALUES (
                :user_id, :document_type, :document_type_id,
                :file_path, :file_name, :file_size,
                NOW(), 'pending'
            )
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':document_type' => $docInfo['type'],
            ':document_type_id' => $docInfo['id'],
            ':file_path' => $uploadResult['path'],
            ':file_name' => $uploadResult['name'],
            ':file_size' => $uploadResult['size']
        ]);
    }
}

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
    
    // Get form type and map to professional category
    $formType = $_POST['form_type'] ?? 'docenteForm';
    
    $categoryMap = [
        'docenteForm' => 1,        // Docente
        'docentePosForm' => 2,     // Docente Pós-Graduação
        'interpreteForm' => 3,     // Intérprete de Libras
        'tecnicoForm' => 4         // Apoio Técnico
    ];
    
    $professionalCategoryId = $categoryMap[$formType] ?? 1;
    
    // Validate required fields
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['cpf'])) {
        throw new Exception('Campos obrigatórios não preenchidos');
    }
    
    // Clean and validate CPF
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);
    if (!validateCPF($cpf)) {
        throw new Exception('CPF inválido');
    }
    
    // Validate email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('E-mail inválido');
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE cpf = :cpf OR email = :email");
    $stmt->execute([
        ':cpf' => $cpf,
        ':email' => $_POST['email']
    ]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing user
        $userId = $existingUser['id'];
        
        $stmt = $pdo->prepare("
            UPDATE users SET
                name = :name,
                email = :email,
                rg = :rg,
                rg_issuer = :rg_issuer,
                rg_state = :rg_state,
                phone = :phone,
                address = :address,
                address_number = :address_number,
                address_complement = :address_complement,
                neighborhood = :neighborhood,
                city = :city,
                state = :state,
                zip_code = :zip_code,
                professional_category_id = :professional_category_id,
                education_level = :education_level,
                updated_at = NOW()
            WHERE id = :user_id
        ");
        
        $updateData = [
            ':user_id' => $userId,
            ':name' => $_POST['name'],
            ':email' => $_POST['email'],
            ':rg' => $_POST['rg'] ?? null,
            ':rg_issuer' => $_POST['rgEmissor'] ?? null,
            ':rg_state' => $_POST['rgUf'] ?? null,
            ':phone' => $_POST['phone'] ?? null,
            ':address' => $_POST['address'] ?? null,
            ':address_number' => $_POST['addNumber'] ?? null,
            ':address_complement' => $_POST['addComplement'] ?? null,
            ':neighborhood' => $_POST['neighborhood'] ?? null,
            ':city' => $_POST['city'] ?? null,
            ':state' => $_POST['state'] ?? null,
            ':zip_code' => $_POST['zipCode'] ?? null,
            ':professional_category_id' => $professionalCategoryId,
            ':education_level' => $_POST['scholarship'] ?? null
        ];
        
        $stmt->execute($updateData);
        
    } else {
        // Create new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                name, email, cpf, rg, rg_issuer, rg_state,
                phone, address, address_number, address_complement,
                neighborhood, city, state, zip_code,
                professional_category_id, education_level,
                has_special_needs, special_needs_description,
                created_at
            ) VALUES (
                :name, :email, :cpf, :rg, :rg_issuer, :rg_state,
                :phone, :address, :address_number, :address_complement,
                :neighborhood, :city, :state, :zip_code,
                :professional_category_id, :education_level,
                :has_special_needs, :special_needs_description,
                NOW()
            )
        ");
        
        // Handle special needs
        $hasSpecialNeeds = ($_POST['specialNeeds'] ?? 'no') === 'yes' ? 1 : 0;
        $specialNeedsDescription = $hasSpecialNeeds ? ($_POST['specialNeedsDetails'] ?? null) : null;
        
        $insertData = [
            ':name' => $_POST['name'],
            ':email' => $_POST['email'],
            ':cpf' => $cpf,
            ':rg' => $_POST['rg'] ?? null,
            ':rg_issuer' => $_POST['rgEmissor'] ?? null,
            ':rg_state' => $_POST['rgUf'] ?? null,
            ':phone' => $_POST['phone'] ?? null,
            ':address' => $_POST['address'] ?? null,
            ':address_number' => $_POST['addNumber'] ?? null,
            ':address_complement' => $_POST['addComplement'] ?? null,
            ':neighborhood' => $_POST['neighborhood'] ?? null,
            ':city' => $_POST['city'] ?? null,
            ':state' => $_POST['state'] ?? null,
            ':zip_code' => $_POST['zipCode'] ?? null,
            ':professional_category_id' => $professionalCategoryId,
            ':education_level' => $_POST['scholarship'] ?? null,
            ':has_special_needs' => $hasSpecialNeeds,
            ':special_needs_description' => $specialNeedsDescription
        ];
        
        $stmt->execute($insertData);
        $userId = $pdo->lastInsertId();
    }
    
    // Process uploaded documents
    $uploadedCount = 0;
    $failedUploads = [];
    
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
            
            // Handle special case for unified documents field
            if ($docInfo['type'] === 'unified') {
                // For unified documents, we create a single merged document entry
                $docInfo['type'] = 'category';
                $docInfo['id'] = 99; // Special ID for unified documents
            }
            
            // Upload the file
            $uploadResult = uploadDocument($file, $userId, $docInfo);
            
            // Save to database
            saveDocumentRecord($pdo, $userId, $docInfo, $uploadResult);
            
            $uploadedCount++;
            
        } catch (Exception $e) {
            error_log("Failed to upload {$fieldName}: " . $e->getMessage());
            $failedUploads[] = $fieldName;
        }
    }
    
    // Process education records (if provided)
    if (isset($_POST['course']) && is_array($_POST['course'])) {
        // Delete old education records
        $stmt = $pdo->prepare("DELETE FROM user_education WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        
        // Insert new education records
        $stmt = $pdo->prepare("
            INSERT INTO user_education (
                user_id, course, institution, created_at
            ) VALUES (
                :user_id, :course, :institution, NOW()
            )
        ");
        
        for ($i = 0; $i < count($_POST['course']); $i++) {
            if (!empty($_POST['course'][$i])) {
                $stmt->execute([
                    ':user_id' => $userId,
                    ':course' => $_POST['course'][$i],
                    ':institution' => $_POST['institution'][$i] ?? ''
                ]);
            }
        }
    }
    
    // Process positions/services (if provided)
    if (isset($_POST['position']) && is_array($_POST['position'])) {
        // Delete old positions
        $stmt = $pdo->prepare("DELETE FROM user_positions WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        
        // Insert new positions
        $stmt = $pdo->prepare("
            INSERT INTO user_positions (user_id, position_id, created_at)
            VALUES (:user_id, :position_id, NOW())
        ");
        
        foreach ($_POST['position'] as $positionId) {
            $stmt->execute([
                ':user_id' => $userId,
                ':position_id' => intval($positionId)
            ]);
        }
    }
    
    // Create or update registration record
    $stmt = $pdo->prepare("
        SELECT id FROM registrations 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $userId]);
    $existingRegistration = $stmt->fetch();
    
    if ($existingRegistration) {
        // Update existing registration
        $registrationId = $existingRegistration['id'];
        
        $stmt = $pdo->prepare("
            UPDATE registrations SET
                professional_category_id = :professional_category_id,
                status = 'pending',
                submission_date = NOW(),
                updated_at = NOW()
            WHERE id = :registration_id
        ");
        
        $stmt->execute([
            ':registration_id' => $registrationId,
            ':professional_category_id' => $professionalCategoryId
        ]);
        
    } else {
        // Create new registration
        $stmt = $pdo->prepare("
            INSERT INTO registrations (
                user_id, professional_category_id, status,
                submission_date, created_at
            ) VALUES (
                :user_id, :professional_category_id, 'pending',
                NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':professional_category_id' => $professionalCategoryId
        ]);
        
        $registrationId = $pdo->lastInsertId();
    }
    
    // Log the submission
    $stmt = $pdo->prepare("
        INSERT INTO registration_logs (
            registration_id, action, details, created_at
        ) VALUES (
            :registration_id, 'submitted', :details, NOW()
        )
    ");
    
    $details = "Cadastro enviado. Documentos: {$uploadedCount} enviados.";
    if (count($failedUploads) > 0) {
        $details .= " Falhas: " . implode(', ', $failedUploads);
    }
    
    $stmt->execute([
        ':registration_id' => $registrationId,
        ':details' => $details
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare response message
    $message = 'Cadastro realizado com sucesso!';
    if (count($failedUploads) > 0) {
        $message .= ' Alguns documentos não puderam ser enviados.';
    }
    
    // Send success response
    sendResponse(true, $message, [
        'registration_id' => $registrationId,
        'user_id' => $userId,
        'uploaded_documents' => $uploadedCount,
        'failed_uploads' => $failedUploads,
        'redirect' => 'confirmation.php?id=' . $registrationId
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on database error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log('Database error in process_registration.php: ' . $e->getMessage());
    
    // Check for duplicate entry
    if ($e->getCode() == '23000') {
        if (strpos($e->getMessage(), 'cpf') !== false) {
            sendResponse(false, 'Este CPF já está cadastrado no sistema.');
        } elseif (strpos($e->getMessage(), 'email') !== false) {
            sendResponse(false, 'Este e-mail já está cadastrado no sistema.');
        } else {
            sendResponse(false, 'Dados duplicados encontrados. Verifique suas informações.');
        }
    } else {
        sendResponse(false, 'Erro ao processar cadastro. Por favor, tente novamente.');
    }
    
} catch (Exception $e) {
    // Rollback transaction on general error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log('General error in process_registration.php: ' . $e->getMessage());
    sendResponse(false, $e->getMessage());
}
?>