<?php 
// pages/interprete.php - Complete version with authentication
session_start();
require_once '../backend/classes/database.class.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user can access this profile
$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_type = $_SESSION['user_type'] ?? '';
$is_admin = ($user_type === 'admin');
$is_own_profile = false;

if (!$requested_id) {
    // No ID provided
    if (!$is_admin && $_SESSION['user_type'] === 'interpreter') {
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
    $interpreter_id = $requested_id;
} elseif ($_SESSION['user_type'] === 'interpreter' && $_SESSION['type_id'] == $requested_id) {
    // User viewing their own profile
    $interpreter_id = $requested_id;
    $is_own_profile = true;
} else {
    // Not authorized
    header('Location: home.php');
    exit();
}

// Include styles and header
echo '<link rel="stylesheet" href="../styles/user.css">';
include '../components/header.php';

require_once '../pdf/assets/title_case.php';

// Get interpreter data
$conection = new Database();
$conn = $conection->connect();

try {
    // Get interpreter data
    $sql = "SELECT i.*, a.* 
            FROM interpreter i
            LEFT JOIN address a ON i.address_id = a.id
            WHERE i.id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $interpreter_id]);
    $interpreter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$interpreter) {
        throw new Exception("Intérprete não encontrado");
    }
    
    // Get documents
    $docSql = "SELECT * FROM documents WHERE interpreter_id = :id";
    $docStmt = $conn->prepare($docSql);
    $docStmt->execute([':id' => $interpreter_id]);
    $document = $docStmt->fetch(PDO::FETCH_ASSOC);
    
    // Extract data
    $name = $interpreter['name'];
    $document_number = $interpreter['document_number'];
    $document_emissor = $interpreter['document_emissor'];
    $document_uf = $interpreter['document_uf'];
    $phone = $interpreter['phone'];
    $cpf = $interpreter['cpf'];
    $email = $interpreter['email'];
    $special_needs = $interpreter['special_needs'];
    $created_at = $interpreter['created_at'];
    $enabled = $interpreter['enabled'];
    
    // Address
    $address = $interpreter['address'] ?? '';
    $city = $interpreter['city'] ?? '';
    $state = $interpreter['state'] ?? '';
    $zip = $interpreter['zip'] ?? '';
    
    // Document path
    $file_path = $document['file_path'] ?? '';
    
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
    $date = new DateTime($created_at);
    $dateF = $date->format('d/m/Y H:i');

    // Format filepath
    $path = '';
    if ($file_path) {
        $string = $file_path;
        $position = strpos($string, "interpretes");
        if ($position !== false) {
            $start = $position + strlen("interpretes/");
            $path = substr($string, $start);
        }
    }
    
} catch (Exception $e) {
    echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>Erro ao carregar dados: ' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="interpretes.php" class="btn btn-primary">Voltar para lista de intérpretes</a>
          </div>';
    include '../components/footer.php';
    exit();
}
?>

<div class="container container-user">
  <?php if ($is_own_profile): ?>
  <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
    <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
    <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
  </div>
  <?php endif; ?>
  
  <?php if ($is_admin): ?>
  <a href="interpretes.php" class="back-link">Voltar</a>
  <?php endif; ?>
  
  <h1 class="main-title">Dados do Intérprete</h1>

  <div class="info-section">
    <h3>Dados pessoais</h3>
    <div class="row">
      <div class="col-9">
        <p class="col-12"><strong>Nome</strong></p>
        <p class="col-12"><?= titleCase($name) ?></p>
      </div>
      <div class="col-3">
        <p class="col-12"><strong>Data de Inscrição</strong></p>
        <p class="col-12"><?= $dateF ?></p>
      </div>
    </div>
    <div class="row">
      <p class="col-12"><strong>Telefone</strong></p>
      <p class="col-12"><?= $phone ?></p>
    </div>
    <div class="row">
      <div class="col-6">
        <p><strong>Documento de Identidade</strong></p>
        <p><?= $document_number ?></p>
      </div>
      <div class="col-4">
        <p><strong>Órgão Emissor</strong></p>
        <p><?= $document_emissor ?></p>
      </div>
      <div class="col-2">
        <p><strong>UF</strong></p>
        <p><?= strtoupper($document_uf) ?></p>
      </div>
    </div>
    <div class="row">
      <p class="col-12"><strong>CPF</strong></p>
      <p class="col-12"><?= $cpf ?></p>
    </div>
    <div class="row">
      <p class="col-12"><strong>Email</strong></p>
      <p class="col-12"><?= $email ?></p>
    </div>
    <div class="row">
      <p class="col-12"><strong>Endereço</strong></p>
      <p class="col-12"><?= titleCase($address) . ', ' . titleCase($city) . ' - ' . strtoupper($state) . ', CEP: ' . $zip ?></p>
    </div>
    <?php if($special_needs != 'Não'): ?>
    <div class="row">
      <p class="col-12"><strong>Necessidades Especiais</strong></p>
      <p class="col-12"><?= $special_needs ?></p>
    </div>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Documentos</h3>
    <?php if (!empty($path)): ?>
      <a href="../backend/documentos/interpretes/<?=$path?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
    <?php endif; ?>
  </div>

  <?php if ($is_own_profile): ?>
  <!-- Password Change Section -->
  <div class="info-section">
    <h3>Alterar Senha</h3>
    
    <?php if(isset($_SESSION['password_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['password_message']) ?>
        </div>
        <?php unset($_SESSION['password_message']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['password_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['password_error']) ?>
        </div>
        <?php unset($_SESSION['password_error']); ?>
    <?php endif; ?>
    
    <?php if($_SESSION['first_login'] ?? false): ?>
        <div class="alert alert-warning">
            <strong>Primeiro acesso!</strong> Por segurança, recomendamos que você altere sua senha.
        </div>
    <?php endif; ?>
    
    <form method="post" action="../auth/process_change_password.php" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="current_password">Senha Atual</label>
                <input type="password" class="form-control" id="current_password" 
                       name="current_password" required>
                <small class="form-text text-muted">
                    Se é seu primeiro acesso, use seu CPF (apenas números)
                </small>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="new_password">Nova Senha</label>
                <input type="password" class="form-control" id="new_password" 
                       name="new_password" required minlength="8">
                <small class="form-text text-muted">
                    Mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos (@$!%*?&)
                </small>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="confirm_password">Confirmar Nova Senha</label>
                <input type="password" class="form-control" id="confirm_password" 
                       name="confirm_password" required>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Alterar Senha</button>
    </form>
  </div>
  <?php endif; ?>

  <?php if($is_admin): ?>
  <div class="info-section">
    <h3>Status do Intérprete</h3>
    <div class="row">
      <p class="col-3"><strong>Status:</strong></p>
      <p class="col-9 user-status <?= $statusClass ?>"><?= $statusText ?></p>
    </div>
    <div class="row">
      <button class="btn ok-btn" onclick="updateInterpreterStatus(<?= $interpreter_id ?>, 1)"
              <?= $enabled == 1 ? 'disabled' : '' ?>>Aprovar</button>
      <button class="btn cancel-btn" onclick="updateInterpreterStatus(<?= $interpreter_id ?>, 0)"
              <?= $enabled == 0 ? 'disabled' : '' ?>>Reprovar</button>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php 
  include '../components/footer.php';
?>

<script>
// Password validation
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    if (newPassword !== this.value) {
        this.setCustomValidity('As senhas devem ser iguais');
    } else {
        this.setCustomValidity('');
    }
});

<?php if($is_admin): ?>
function updateInterpreterStatus(interpreterId, status) {
  if(confirm("Tem certeza que deseja alterar o status do intérprete?")) {
    fetch('../backend/api/update_interpreter_status.php', {
      method: 'POST',
      headers: {
        'Content-type': 'application/json'
      },
      body: JSON.stringify({
        interpreter_id: interpreterId,
        status: status
      })
    })
    .then(response=> response.json())
    .then(data => {
      if(data.success) {
        const statusElement = document.querySelector('.user-status');
        const enableButton = document.querySelector('.ok-btn');
        const disableButton = document.querySelector('.cancel-btn');
        
        const statusText = status === 1 ? 'Apto' : 'Inapto';

        statusElement.textContent = statusText;
        statusElement.className = 'user-status ' + (status === 1 ? 'status-approved' : 'status-not-approved');

        enableButton.disabled = (status === 1);
        disableButton.disabled = (status === 0);         

        Toastify({
          text: "Status do intérprete atualizado!",
          className: "statusToast",
          style: {
            background: "#38b000",
          },
        }).showToast();

      } else {
        alert('Erro ao atualizar o status' + (data.message || 'Erro desconhecido'))
      }
    })
    .catch(error => {
      console.error('Erro: ', error);
      alert('Erro ao atualizar o status');
    })
  }
}
<?php endif; ?>
</script>