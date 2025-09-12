/**
 * Save document record to database with fixed structure
 */
function saveDocumentRecord($pdo, $userId, $docInfo, $uploadResult) {
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
            ':document_type_id' => $docInfo['type_id']
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
        } else<?php
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
    
    // Docente Documents
    'formacao_escolar_docente' => [
        'type' => 'qualification',
        'id' => 7,
        'required' => false, // Will be required if docente role is selected
        'name' => 'Formação Escolar',
        'roles' => ['docente', 'docente-pos']
    ],
    'experiencia_profissional_docente' => [
        'type' => 'qualification',
        'id' => 8,
        'required' => false,
        'name' => 'Experiência Profissional',
        'roles' => ['docente', 'docente-pos']
    ],
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
    
    // Intérprete Documents
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
    
    // Financial Documents
    'certidao_estadual' => [
        'type' => 'financial',
        'id' => 13,
        'required' => true,
        'name' => 'Certidão Negativa Estadual'
    ],
    'certidao_municipal' => [
        'type' => 'financial',
        'id' => 14,
        'required' => true,
        'name' => 'Certidão Negativa Municipal'
    ],
    'certidao_federal' => [
        'type' => 'financial',
        'id' => 15,
        'required' => true,
        'name' => 'Certidão Negativa Federal'
    ],
    'certidao_conjunta' => [
        'type' => 'financial',
        'id' => 16,
        'required' => true,
        'name' => 'Certidão Conjunta PGFN e RFB'
    ]
];

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Send JSON response and exit
 */
function sendResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
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
 * Upload a document file with decentralized structure
 */
function uploadDocument($file, $userId, $docInfo, $roles) {
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
function saveDocumentRecord($pdo, $userId, $docInfo, $uploadResult) {
    try {
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
    } catch (Exception $e) {
        error_log("Error saving document: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Process role-specific data
 */
function processRoleSpecificData($pdo, $userId, $roles, $postData) {
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
            switch($role) {
                case 'docente':
                    processDocenteData($pdo, $userId, $postData);
                    break;
                case 'docente-pos':
                    processDocentePosData($pdo, $userId, $postData);
                    break;
                case 'interprete':
                    processInterpreteData($pdo, $userId, $postData);
                    break;
                case 'tecnico':
                    processTecnicoData($pdo, $userId, $postData);
                    break;
            }
        }
    }
    
    // Store user roles
    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id, created_at)
        VALUES (:user_id, :role_id, NOW())
    ");
    
    foreach ($roleIds as $roleId) {
        $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }
    
    return $roleIds;
}

function processDocenteData($pdo, $userId, $postData) {
    // Process docente categories
    if (isset($postData['docente_categories']) && is_array($postData['docente_categories'])) {
        $stmt = $pdo->prepare("
            INSERT INTO user_teacher_categories (user_id, category_id, created_at)
            VALUES (:user_id, :category_id, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        
        foreach ($postData['docente_categories'] as $categoryId) {
            $stmt->execute([
                ':user_id' => $userId,
                ':category_id' => intval($categoryId)
            ]);
        }
    }
}

function processDocentePosData($pdo, $userId, $postData) {
    // Process docente pos categories
    if (isset($postData['docente_pos_categories']) && is_array($postData['docente_pos_categories'])) {
        $stmt = $pdo->prepare("
            INSERT INTO user_teacher_categories (user_id, category_id, is_postgrad, created_at)
            VALUES (:user_id, :category_id, 1, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        
        foreach ($postData['docente_pos_categories'] as $categoryId) {
            $stmt->execute([
                ':user_id' => $userId,
                ':category_id' => intval($categoryId)
            ]);
        }
    }
    
    // Process disciplines
    if (isset($postData['discipline']) && is_array($postData['discipline'])) {
        $stmt = $pdo->prepare("DELETE FROM teacher_disciplines WHERE teacher_id = :teacher_id");
        $stmt->execute([':teacher_id' => $userId]);
        
        $stmt = $pdo->prepare("
            INSERT INTO teacher_disciplines (teacher_id, discipline_name, created_at)
            VALUES (:teacher_id, :discipline_name, NOW())
        ");
        
        foreach ($postData['discipline'] as $discipline) {
            if (!empty(trim($discipline))) {
                $stmt->execute([
                    ':teacher_id' => $userId,
                    ':discipline_name' => trim($discipline)
                ]);
            }
        }
    }
}

function processInterpreteData($pdo, $userId, $postData) {
    // Process interpreter experience
    if (isset($postData['interprete_experience']) && !empty($postData['interprete_experience'])) {
        $stmt = $pdo->prepare("
            INSERT INTO user_interprete_info (user_id, experience_description, created_at)
            VALUES (:user_id, :experience, NOW())
            ON DUPLICATE KEY UPDATE 
                experience_description = :experience,
                updated_at = NOW()
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':experience' => $postData['interprete_experience']
        ]);
    }
}

function processTecnicoData($pdo, $userId, $postData) {
    // Técnico doesn't need additional processing beyond common fields
    // Just log that this user has the técnico role
    $stmt = $pdo->prepare("
        INSERT INTO user_technician_info (user_id, created_at)
        VALUES (:user_id, NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()
    ");
    
    $stmt->execute([':user_id' => $userId]);
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
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE cpf = :cpf");
    $stmt->execute([':cpf' => $cpfClean]);
    $existingUser = $stmt->fetch();
    
    // Process special needs
    $hasSpecialNeeds = isset($_POST['specialNeeds']) && $_POST['specialNeeds'] === 'yes';
    $specialNeedsDescription = $hasSpecialNeeds ? ($_POST['specialNeedsDetails'] ?? null) : null;
    
    if ($existingUser) {
        // Update existing user
        $userId = $existingUser['id'];
        
        $stmt = $pdo->prepare("
            UPDATE users SET
                name = :name,
                email = :email,
                phone = :phone,
                address = :address,
                address_number = :address_number,
                address_complement = :address_complement,
                neighborhood = :neighborhood,
                city = :city,
                state = :state,
                zip_code = :zip_code,
                education_level = :education_level,
                has_special_needs = :has_special_needs,
                special_needs_description = :special_needs_description,
                updated_at = NOW()
            WHERE id = :user_id
        ");
        
        $updateData = [
            ':user_id' => $userId,
            ':name' => $_POST['name'] ?? null,
            ':email' => $_POST['email'] ?? null,
            ':phone' => $_POST['phone'] ?? null,
            ':address' => $_POST['address'] ?? null,
            ':address_number' => $_POST['addNumber'] ?? null,
            ':address_complement' => $_POST['addComplement'] ?? null,
            ':neighborhood' => $_POST['neighborhood'] ?? null,
            ':city' => $_POST['city'] ?? null,
            ':state' => $_POST['state'] ?? null,
            ':zip_code' => $_POST['zipCode'] ?? null,
            ':education_level' => $_POST['scholarship'] ?? null,
            ':has_special_needs' => $hasSpecialNeeds,
            ':special_needs_description' => $specialNeedsDescription
        ];
        
        $stmt->execute($updateData);
        
    } else {
        // Create new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                cpf, rg, rg_issuer, rg_uf, name, email, phone, 
                address, address_number, address_complement,
                neighborhood, city, state, zip_code,
                education_level, has_special_needs, 
                special_needs_description, created_at
            ) VALUES (
                :cpf, :rg, :rg_issuer, :rg_uf, :name, :email, :phone,
                :address, :address_number, :address_complement,
                :neighborhood, :city, :state, :zip_code,
                :education_level, :has_special_needs,
                :special_needs_description, NOW()
            )
        ");
        
        $insertData = [
            ':cpf' => $cpfClean,
            ':rg' => $_POST['rg'] ?? null,
            ':rg_issuer' => $_POST['rgEmissor'] ?? null,
            ':rg_uf' => $_POST['rgUf'] ?? null,
            ':name' => $_POST['name'] ?? null,
            ':email' => $_POST['email'] ?? null,
            ':phone' => $_POST['phone'] ?? null,
            ':address' => $_POST['address'] ?? null,
            ':address_number' => $_POST['addNumber'] ?? null,
            ':address_complement' => $_POST['addComplement'] ?? null,
            ':neighborhood' => $_POST['neighborhood'] ?? null,
            ':city' => $_POST['city'] ?? null,
            ':state' => $_POST['state'] ?? null,
            ':zip_code' => $_POST['zipCode'] ?? null,
            ':education_level' => $_POST['scholarship'] ?? null,
            ':has_special_needs' => $hasSpecialNeeds,
            ':special_needs_description' => $specialNeedsDescription
        ];
        
        $stmt->execute($insertData);
        $userId = $pdo->lastInsertId();
    }
    
    // Process education data
    if (isset($_POST['course']) && is_array($_POST['course'])) {
        // Delete existing education records
        $stmt = $pdo->prepare("DELETE FROM user_education WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        
        // Insert new education records
        $stmt = $pdo->prepare("
            INSERT INTO user_education (user_id, course, institution, created_at)
            VALUES (:user_id, :course, :institution, NOW())
        ");
        
        for ($i = 0; $i < count($_POST['course']); $i++) {
            if (!empty($_POST['course'][$i]) && !empty($_POST['institution'][$i])) {
                $stmt->execute([
                    ':user_id' => $userId,
                    ':course' => $_POST['course'][$i],
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
    
    // Create registration record for each selected role
    foreach ($roleIds as $roleId) {
        $stmt = $pdo->prepare("
            INSERT INTO registrations (
                user_id, professional_category_id, status,
                submission_date, created_at
            ) VALUES (
                :user_id, :professional_category_id, 'pending',
                NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                status = 'pending',
                submission_date = NOW(),
                updated_at = NOW()
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':professional_category_id' => $roleId
        ]);
    }
    
    // Log the submission
    $stmt = $pdo->prepare("
        INSERT INTO registration_logs (
            user_id, action, details, ip_address, user_agent, created_at
        ) VALUES (
            :user_id, 'registration_submitted', :details, :ip_address, :user_agent, NOW()
        )
    ");
    
    $details = json_encode([
        'roles' => $selectedRoles,
        'documents_uploaded' => $uploadedCount,
        'failed_uploads' => $failedUploads,
        'document_ids' => $documentIds
    ]);
    
    $stmt->execute([
        ':user_id' => $userId,
        ':details' => $details,
        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare response message
    $message = 'Cadastro realizado com sucesso!';
    if ($uploadedCount > 0) {
        $message .= " {$uploadedCount} documento(s) enviado(s).";
    }
    if (count($failedUploads) > 0) {
        $message .= " Alguns documentos falharam no envio.";
    }
    
    sendResponse(true, $message, [
        'user_id' => $userId,
        'documents_uploaded' => $uploadedCount,
        'failed_uploads' => $failedUploads,
        'roles' => $selectedRoles
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Registration error: " . $e->getMessage());
    sendResponse(false, 'Erro ao processar cadastro: ' . $e->getMessage());
}
?>