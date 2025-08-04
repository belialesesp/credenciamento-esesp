<?php
// pages/docente.php
require_once '../init.php'; 


// Check authentication BEFORE any output
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user can access this profile
$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_type = $_SESSION['user_type'] ?? '';
$is_admin = isAdmin();
$is_own_profile = false;

if (!$requested_id) {
    // No ID provided
    if (!$is_admin && $_SESSION['user_type'] === 'teacher') {
        // Redirect to their own profile
        header('Location: ?id=' . $_SESSION['type_id']);
        exit();
    } else {
        header('Location: home.php');
        exit();
    }
}

// Check access permissions
if ($is_admin) {
    // Admin can see all profiles
    $teacher_id = $requested_id;
} elseif ($_SESSION['user_type'] === 'teacher' && $_SESSION['type_id'] == $requested_id) {
    // User viewing their own profile
    $teacher_id = $requested_id;
    $is_own_profile = true;
} else {
    // Not authorized
    header('Location: home.php');
    exit();
}

// Get database connection
$conection = new Database();
$conn = $conection->connect();

// Get teacher data BEFORE including header
try {
    // Using unified user table
    $sql = "SELECT u.*, 
            GROUP_CONCAT(ur.role) as roles
            FROM user u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            WHERE u.id = :id
            GROUP BY u.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        // Redirect if teacher not found
        header('Location: home.php');
        exit();
    }

    // Extract data
    $name = $teacher['name'];
    $document_number = $teacher['document_number'] ?? '';
    $document_emissor = $teacher['document_emissor'] ?? '';
    $document_uf = $teacher['document_uf'] ?? '';
    $phone = $teacher['phone'] ?? '';
    $cpf = $teacher['cpf'];
    $email = $teacher['email'];
    $special_needs = $teacher['special_needs'] ?? '';
    $created_at = $teacher['created_at'];
    $enabled = $teacher['enabled'] ?? null;
    
    // Address fields from unified table
    $street = $teacher['street'] ?? '';
    $number = $teacher['number'] ?? '';
    $neighborhood = $teacher['neighborhood'] ?? '';
    $city = $teacher['city'] ?? '';
    $state = $teacher['state'] ?? '';
    $zip_code = $teacher['zip_code'] ?? '';
    
    // Get roles
    $userRoles = $teacher['roles'] ? explode(',', $teacher['roles']) : [];

} catch (Exception $e) {
    error_log("Error loading teacher data: " . $e->getMessage());
    header('Location: home.php');
    exit();
}

// NOW include the header after all redirects
echo '<link rel="stylesheet" href="../styles/user.css">';
include '../components/header.php';

require_once '../pdf/assets/title_case.php';

// Status display
$statusText = match ($enabled) {
    1 => 'Apto',
    0 => 'Inapto',
    default => 'Aguardando aprovação',
};

$statusClass = match ($enabled) {
    1 => 'status-approved',
    0 => 'status-not-approved',
    default => 'status-pending',
};

// Format date
if ($created_at) {
    $date = new DateTime($created_at);
    $dateF = $date->format('d/m/Y H:i');
} else {
    $dateF = 'N/A';
}
?>

<div class="container">
    <h1 class="main-title">Perfil do Docente</h1>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informações Pessoais</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nome:</strong> <?= htmlspecialchars($name) ?></p>
                            <p><strong>CPF:</strong> <?= htmlspecialchars($cpf) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                            <p><strong>Telefone:</strong> <?= htmlspecialchars($phone ?: 'Não informado') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Documento:</strong> <?= htmlspecialchars($document_number ?: 'Não informado') ?></p>
                            <?php if ($document_emissor): ?>
                                <p><strong>Emissor:</strong> <?= htmlspecialchars($document_emissor) ?> - <?= htmlspecialchars($document_uf) ?></p>
                            <?php endif; ?>
                            <p><strong>Necessidades Especiais:</strong> <?= htmlspecialchars($special_needs ?: 'Nenhuma') ?></p>
                            <p><strong>Cadastrado em:</strong> <?= $dateF ?></p>
                        </div>
                    </div>
                    
                    <!-- Show roles -->
                    <div class="mt-3">
                        <p><strong>Perfis:</strong></p>
                        <ul class="list-unstyled">
                            <?php foreach ($userRoles as $role): ?>
                                <li><span class="badge bg-info"><?= getRoleDisplayName($role) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if ($street || $city): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Endereço</h5>
                </div>
                <div class="card-body">
                    <?php if ($street): ?>
                        <p><?= htmlspecialchars($street) ?><?= $number ? ', ' . htmlspecialchars($number) : '' ?></p>
                    <?php endif; ?>
                    <?php if ($neighborhood): ?>
                        <p><?= htmlspecialchars($neighborhood) ?></p>
                    <?php endif; ?>
                    <?php if ($city || $state): ?>
                        <p><?= htmlspecialchars($city) ?><?= $state ? ' - ' . htmlspecialchars($state) : '' ?></p>
                    <?php endif; ?>
                    <?php if ($zip_code): ?>
                        <p>CEP: <?= htmlspecialchars($zip_code) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Status</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge <?= $statusClass ?> fs-5 p-3">
                        <?= $statusText ?>
                    </span>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Ações</h5>
                </div>
                <div class="card-body">
                    <?php if ($is_own_profile || $is_admin): ?>
                        <a href="edit_teacher.php?id=<?= $teacher_id ?>" class="btn btn-primary btn-sm mb-2 w-100">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($is_admin): ?>
                        <a href="approve_teacher.php?id=<?= $teacher_id ?>" class="btn btn-success btn-sm mb-2 w-100">
                            <i class="fas fa-check"></i> Gerenciar Aprovação
                        </a>
                        <a href="docentes.php" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-arrow-left"></i> Voltar para Lista
                        </a>
                    <?php else: ?>
                        <a href="home.php" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-home"></i> Voltar ao Início
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>