<?php
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/classes/database.class.php';
require_once '../backend/api/get_registers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$isAdmin = false;
if (isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles'])) {
  $is_admin = isAdministrativeRole();
}
// Helper function to truncate text
function truncate_text($text, $length = 50)
{
  if (strlen($text) > $length) {
    return substr($text, 0, $length) . '...';
  }
  return $text;
}

$conection = new Database();
$conn = $conection->connect();

$technicians = get_technicians($conn);
$_SESSION['user-data'] = $technicians;
?>

<style>
  .filter-container {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    align-items: flex-end;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 200px;
  }

  .filter-group label {
    font-weight: 500;
    color: #555;
    font-size: 14px;
  }

  .filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
  }

  .export-button {
    margin-left: auto;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .technician-row {
    cursor: pointer;
  }

  .technician-row:hover {
    background-color: #f5f5f5;
  }

  .status-approved {
    color: #155724;
    background-color: #d4edda;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
  }

  .status-not-approved {
    color: #721c24;
    background-color: #f8d7da;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
  }

  .status-pending {
    color: #856404;
    background-color: #fff3cd;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
  }

  .export-status {
    margin-left: 10px;
    font-size: 14px;
  }

  /* Sorting styles */
  .sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 20px;
  }

  .sortable:hover {
    background-color: #f0f0f0;
  }

  .sort-indicator {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #666;
  }

  .sort-indicator.active {
    color: #333;
    font-weight: bold;
  }

  .action-button {
    margin-left: 10px;
    padding: 4px 12px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s ease;
  }

  .action-button:hover {
    background-color: #218838;
  }

  .invitation-pending {
    display: inline-block;
    padding: 4px 12px;
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
    font-weight: 500;
  }

  .invitation-pending:before {
    content: "⏱ ";
    font-size: 14px;
  }

  .invitation-rejected {
    display: inline-block;
    padding: 4px 12px;
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
    font-weight: 500;
  }

  .invitation-rejected:before {
    content: "✗ ";
    font-size: 14px;
  }

  .invitation-accepted {
    display: inline-block;
    padding: 4px 12px;
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
    font-weight: 500;
  }

  .invitation-accepted:before {
    content: "✓ ";
    font-size: 14px;
  }

  .invitation-contracted {
    display: inline-block;
    padding: 4px 12px;
    background-color: #cfe2ff;
    color: #084298;
    border: 1px solid #9ec5fe;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
    font-weight: 500;
  }

  .invitation-contracted:before {
    content: "📋 ";
    font-size: 14px;
  }

  /* Modal Styles */
  .modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
    display: none;
  }

  .modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 90%;
    max-width: 600px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .modal-header {
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px 8px 0 0;
  }

  .modal-header h2 {
    margin: 0;
    color: #333;
  }

  .modal-body {
    padding: 20px;
  }

  .close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
  }

  .close:hover,
  .close:focus {
    color: #000;
  }

  .form-group {
    margin-bottom: 15px;
  }

  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
  }

  .form-group p {
    margin: 0;
    padding: 8px 12px;
    background-color: #f8f9fa;
    border-radius: 4px;
  }

  .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
  }

  textarea.form-control {
    resize: vertical;
    min-height: 120px;
  }

  .form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  }

  .btn {
    padding: 8px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .btn-primary {
    background-color: #007bff;
    color: white;
  }

  .btn-primary:hover {
    background-color: #0056b3;
  }

  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }

  .btn-secondary:hover {
    background-color: #545b62;
  }

  /* Contract textarea styles */
  .contract-textarea {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 13px;
    resize: vertical;
    min-height: 60px;
    margin-top: 5px;
    max-width: 300px;
    /* Limit width to prevent table stretching */
  }

  .contract-label {
    display: block;
    font-weight: 600;
    color: #28a745;
    font-size: 12px;
    margin-bottom: 3px;
  }

  .contract-save-btn {
    padding: 4px 12px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    margin-top: 5px;
  }

  .contract-save-btn:hover {
    background-color: #0056b3;
  }

  .invitation-pending {
    display: inline-block;
    padding: 4px 12px;
    background-color: #ffc107;
    color: #212529;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
  }

  /* Called at column specific styles */
  #called-at-header,
  .called-at-cell {
    width: 8rem;
    white-space: nowrap;
  }

  /* Ensure contract div doesn't break table layout */
  td>div[style*="inline-block"] {
    vertical-align: top;
    max-width: 300px;
  }

  /* Responsive adjustments */
  @media (max-width: 1200px) {
    .contract-textarea {
      max-width: 200px;
    }
  }

  .invitation-pending {
    display: inline-block;
    padding: 4px 12px;
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
    font-weight: 500;
  }

  .invitation-pending:before {
    content: "⏱ ";
    font-size: 14px;
  }

  .invitation-rejected {
    display: inline-block;
    padding: 4px 12px;
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 10px;
    font-weight: 500;
  }

  .invitation-rejected:before {
    content: "✗ ";
    font-size: 14px;
  }
</style>

<div class="container">
  <a href="home.php" class="btn btn-info mt-5">← Voltar ao Início</a>
  <h1 class="main-title">Técnicos</h1>

  <div class="filter-container">
    <div class="filter-group">
      <label for="name">Filtrar por nome</label>
      <input type="text" name="name" id="name" placeholder="Digite o nome...">
    </div>
    <div class="filter-group">
      <label for="status">Filtrar por status</label>
      <select name="status" id="status">
        <option value="">Todos</option>
        <option value="1">Apto</option>
        <option value="0">Inapto</option>
        <option value="null">Aguardando</option>
      </select>
    </div>

    <button id="export-pdf-btn" class="export-button btn btn-primary" onclick="exportToPDF()">
      <i class="fas fa-file-pdf"></i> Exportar PDF
    </button>
    <button id="export-btn" class="btn btn-success ml-2" onclick="exportToExcel()">
      <i class="fas fa-file-excel"></i> Exportar Excel
    </button>
    <span class="export-status" id="exportStatus"></span>
  </div>

  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th class="sortable" data-sort="name">
          Nome
          <span class="sort-indicator" id="sort-name"></span>
        </th>
        <th>Email</th>
        <th class="sortable" data-sort="created">
          Data de Inscrição
          <span class="sort-indicator" id="sort-created"></span>
        </th>
        <th>Data de Chamada</th>
        <th>Situação</th>
      </tr>
    </thead>
    <tbody id="techniciansTableBody">
      <!-- Table will be populated by JavaScript -->
    </tbody>
  </table>
</div>
<!-- Staff Invitation Modal -->
<div id="invitationModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Enviar Convite para Técnico</h2>
      <span class="close" onclick="closeInvitationModal()">&times;</span>
    </div>
    <div class="modal-body">
      <form id="invitationForm">
        <input type="hidden" id="technicianId" name="user_id">
        <input type="hidden" id="technicianEmail" name="teacher_email">
        <input type="hidden" name="user_type" value="technician">
        <input type="hidden" name="is_staff" value="true">

        <div class="form-group">
          <label><strong>Técnico:</strong></label>
          <p id="technicianName"></p>
        </div>

        <div class="form-group">
          <label for="messageSubject">Assunto:</label>
          <input type="text" id="messageSubject" name="subject" class="form-control"
            value="Convite para trabalho técnico" required>
        </div>

        <div class="form-group">
          <label for="messageBody">Mensagem:</label>
          <textarea id="messageBody" name="message" class="form-control" rows="6" required>
            Prezado(a) Técnico(a),

            Gostaríamos de convidá-lo(a) para trabalhar conosco.

            Por favor, clique em um dos botões abaixo para aceitar ou recusar este convite.

            Atenciosamente,
            Coordenação Técnica
          </textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enviar Convite</button>
          <button type="button" class="btn btn-secondary" onclick="closeInvitationModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  // User roles and permissions
  const userRoles = <?php echo json_encode($_SESSION['user_roles'] ?? []); ?>;

  function canSendInvites() {
    return userRoles.includes('admin') || userRoles.includes('gedth');
  }

  function canViewContractInfo() {
    return userRoles.includes('admin') || userRoles.includes('gese');
  }

  // For page access check
  const isAdmin = <?= json_encode(isAdministrativeRole()) ?>;

  // Data variables
  let allTechnicians = <?= json_encode($technicians) ?>;
  let currentUsers = [...allTechnicians];
  let currentSort = {
    column: 'called',
    direction: 'desc'
  };
  let invitationStatuses = {};

  // Modal functions
  function openInvitationModal(userId, userName, userEmail) {
    document.getElementById('technicianId').value = userId;
    document.getElementById('technicianEmail').value = userEmail;
    document.getElementById('technicianName').textContent = userName;
    document.getElementById('invitationModal').style.display = 'block';
  }

  function closeInvitationModal() {
    document.getElementById('invitationModal').style.display = 'none';
    document.getElementById('invitationForm').reset();
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('invitationModal');
    if (event.target == modal) {
      closeInvitationModal();
    }
  }

  // Form submission handler
  document.getElementById('invitationForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Enviando...';

    const formData = new FormData(e.target);

    try {
      const response = await fetch('../backend/api/send_course_invitation.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert('Convite enviado com sucesso!');
        closeInvitationModal();

        // Clear cache completely and refresh the data
        invitationStatuses = {};

        // Force refresh of the data
        await fetchFilteredData();

        // Force re-render the table
        await renderTable(currentUsers);
      } else {
        alert('Erro ao enviar convite: ' + (result.message || 'Erro desconhecido'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Erro ao enviar convite. Tente novamente.');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = originalText;
    }
  });

  // Check invitation status
  async function checkInvitationStatus(technicianId) {
    try {
      const response = await fetch(`../backend/api/check_course_invitation_status.php?user_id=${technicianId}&is_staff=true`);
      const data = await response.json();

      if (data.success) {
        return {
          has_pending: data.has_pending || false,
          is_accepted: data.is_accepted || false,
          is_rejected: data.is_rejected || false,
          contract_info: data.contract_info || '',
          hours_passed: data.hours_passed || 0
        };
      } else {
        console.error('API returned success: false', data);
        return null;
      }
    } catch (error) {
      console.error('Error checking invitation status:', error);
      return null;
    }
  }

  // Save contract info
  async function saveContractInfo(userId, contractInfo) {
    try {
      const formData = new FormData();
      formData.append('teacher_id', userId);
      formData.append('contract_info', contractInfo);
      formData.append('is_staff', 'true');

      const response = await fetch('../backend/api/save_contract_info.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert('Informações do contrato salvas com sucesso!');
        await fetchFilteredData();
      } else {
        alert('Erro ao salvar informações do contrato: ' + result.message);
      }
    } catch (error) {
      console.error('Error saving contract info:', error);
      alert('Erro ao salvar informações do contrato.');
    }
  }

  // DOM Content Loaded
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sortable').forEach(th => {
      th.addEventListener('click', () => {
        const sortColumn = th.getAttribute('data-sort');
        handleSort(sortColumn);
      });
    });

    fetchFilteredData();

    document.getElementById('status').addEventListener('change', filterTechnicians);

    sortTechnicians();

    const calledHeader = document.querySelector('th:nth-child(4)');
    if (calledHeader) {
      calledHeader.innerHTML = 'Data de Chamada <span style="font-size: 12px;">↓</span>';
    }
  });

  // Sorting function
  function handleSort(column) {
    if (currentSort.column === column) {
      currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      currentSort.column = column;
      currentSort.direction = 'asc';
    }

    updateSortIndicators();
    sortTechnicians();
    renderTable(currentUsers);
  }

  function updateSortIndicators() {
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
      indicator.textContent = '';
      indicator.classList.remove('active');
    });

    const activeIndicator = document.getElementById(`sort-${currentSort.column}`);
    if (activeIndicator) {
      activeIndicator.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
      activeIndicator.classList.add('active');
    }
  }

  function sortTechnicians() {
    currentUsers.sort((a, b) => {
      const hasCalledAtA = a.called_at && a.called_at.trim() !== '';
      const hasCalledAtB = b.called_at && b.called_at.trim() !== '';

      if (!hasCalledAtA && hasCalledAtB) return -1;
      if (hasCalledAtA && !hasCalledAtB) return 1;

      if (hasCalledAtA && hasCalledAtB) {
        const dateA = parseDate(a.called_at);
        const dateB = parseDate(b.called_at);
        return dateA - dateB;
      }

      const createdA = new Date(a.created_at);
      const createdB = new Date(b.created_at);
      return createdA - createdB;
    });
  }

  function fetchFilteredData() {
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    const queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    fetch('../backend/api/get_filtered_technicians.php?' + queryParams.toString())
      .then(response => response.json())
      .then(data => {
        allTechnicians = data;
        currentTechnicians = [...data];
        sortTechnicians();
        renderTable(currentUsers);
      })
      .catch(error => console.error('Error:', error));
  }

  function filterTechnicians() {
    const statusFilter = document.getElementById('status').value;
    const nameFilter = document.getElementById('name').value.toLowerCase();

    if (!statusFilter && !nameFilter) {
      currentTechnicians = [...allTechnicians];
    } else {
      currentTechnicians = allTechnicians.filter(t => {
        const statusMatch = !statusFilter ||
          (statusFilter === 'null' ? (t.enabled === null || t.enabled === '') :
            String(t.enabled) === statusFilter);

        const nameMatch = !nameFilter ||
          (t.name && t.name.toLowerCase().includes(nameFilter));

        return statusMatch && nameMatch;
      });
    }

    sortTechnicians();
    renderTable(currentUsers);
  }

  document.getElementById('name').addEventListener('input', function() {
    clearTimeout(window.nameFilterTimeout);
    window.nameFilterTimeout = setTimeout(fetchFilteredData, 300);
  });

  // Render table function
  async function renderTable(users) {
    const tbody = document.getElementById('techniciansTableBody');
    tbody.innerHTML = '';

    if (users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum técnico encontrado</td></tr>';
      return;
    }

    const sortedUsers = [...users].sort((a, b) => {
      const hasCalledAtA = a.called_at && a.called_at.trim() !== '';
      const hasCalledAtB = b.called_at && b.called_at.trim() !== '';

      if (!hasCalledAtA && hasCalledAtB) return -1;
      if (hasCalledAtA && !hasCalledAtB) return 1;

      if (!hasCalledAtA && !hasCalledAtB) {
        const createdA = new Date(a.created_at);
        const createdB = new Date(b.created_at);
        return createdA - createdB;
      }

      const dateA = parseDate(a.called_at);
      const dateB = parseDate(b.called_at);
      return dateA - dateB;
    });

    const userInvitationStatuses = {};
    const aptoUsers = sortedUsers.filter(user => user.enabled == 1);

    for (const user of aptoUsers) {
      userInvitationStatuses[user.id] = await checkInvitationStatus(user.id);
    }

    for (const user of sortedUsers) {
      const enabled = user.enabled == 1 ? 'Apto' :
        user.enabled == 0 ? 'Inapto' : 'Aguardando';

      const statusClass = user.enabled == 1 ? 'status-approved' :
        user.enabled == 0 ? 'status-not-approved' : 'status-pending';

      const createdDate = new Date(user.created_at);
      const createdDateF = createdDate.toLocaleDateString('pt-BR') + ' ' +
        createdDate.toLocaleTimeString('pt-BR', {
          hour: '2-digit',
          minute: '2-digit'
        });

      let calledDateF = '-';
      if (user.called_at) {
        const calledDate = parseDate(user.called_at);
        if (!isNaN(calledDate.getTime())) {
          calledDateF = calledDate.toLocaleDateString('pt-BR');
        } else {
          calledDateF = 'Data inválida';
          console.warn('Invalid date format:', user.called_at);
        }
      }

      const row = document.createElement('tr');
      row.className = 'technician-row';
      row.style.cursor = 'pointer';

      const nameCell = document.createElement('td');
      nameCell.textContent = titleCase(user.name);

      const invitationStatus = userInvitationStatuses[user.id];

      if (invitationStatus) {
        if (invitationStatus.has_pending) {
          const pendingSpan = document.createElement('span');
          pendingSpan.className = 'invitation-pending';
          pendingSpan.textContent = 'Aguardando resposta';
          pendingSpan.style.marginLeft = '10px';
          nameCell.appendChild(pendingSpan);
        } else if (invitationStatus.is_accepted) {
          const acceptedSpan = document.createElement('span');
          acceptedSpan.className = 'invitation-contracted';
          acceptedSpan.textContent = 'Contratado';
          acceptedSpan.style.marginLeft = '10px';
          nameCell.appendChild(acceptedSpan);

          if (canViewContractInfo()) {
            const contractDiv = document.createElement('div');
            contractDiv.style.display = 'inline-block';
            contractDiv.style.marginLeft = '10px';

            const label = document.createElement('label');
            label.className = 'contract-label';
            label.textContent = 'Contrato:';

            const textarea = document.createElement('textarea');
            textarea.className = 'contract-textarea';
            textarea.placeholder = 'Informações do contrato...';
            textarea.value = invitationStatus.contract_info || '';

            const saveBtn = document.createElement('button');
            saveBtn.className = 'contract-save-btn';
            saveBtn.textContent = 'Salvar';
            saveBtn.onclick = (e) => {
              e.stopPropagation();
              saveContractInfo(user.id, textarea.value);
            };

            contractDiv.appendChild(label);
            contractDiv.appendChild(textarea);
            contractDiv.appendChild(saveBtn);
            nameCell.appendChild(contractDiv);
          }
        } else if (invitationStatus.is_rejected) {
          const rejectedSpan = document.createElement('span');
          rejectedSpan.className = 'invitation-rejected';
          rejectedSpan.textContent = 'Recusado';
          rejectedSpan.style.marginLeft = '10px';
          nameCell.appendChild(rejectedSpan);
        } else if (canSendInvites() && user.enabled == 1) {
          const inviteBtn = document.createElement('button');
          inviteBtn.className = 'action-button';
          inviteBtn.textContent = 'Enviar Convite';
          inviteBtn.onclick = (e) => {
            e.stopPropagation();
            openInvitationModal(user.id, user.name, user.email);
          };
          nameCell.appendChild(inviteBtn);
        }
      } else if (canSendInvites() && user.enabled == 1) {
        const inviteBtn = document.createElement('button');
        inviteBtn.className = 'action-button';
        inviteBtn.textContent = 'Enviar Convite';
        inviteBtn.onclick = (e) => {
          e.stopPropagation();
          openInvitationModal(user.id, user.name, user.email);
        };
        nameCell.appendChild(inviteBtn);
      }

      row.appendChild(nameCell);

      const emailCell = document.createElement('td');
      emailCell.textContent = user.email.toLowerCase();
      row.appendChild(emailCell);

      const createdCell = document.createElement('td');
      createdCell.textContent = createdDateF;
      row.appendChild(createdCell);

      const calledCell = document.createElement('td');
      calledCell.textContent = calledDateF;
      row.appendChild(calledCell);

      const statusCell = document.createElement('td');
      const statusSpan = document.createElement('span');
      statusSpan.className = statusClass;
      statusSpan.textContent = enabled;
      statusCell.appendChild(statusSpan);
      row.appendChild(statusCell);

      row.onclick = (e) => {
        if (e.target.closest('button, textarea, input')) {
          return;
        }
        window.location.href = `tecnico.php?id=${user.id}`;
      };

      tbody.appendChild(row);
    }
  }

  // Helper functions
  function parseDate(dateString) {
    if (!dateString) return new Date(NaN);

    if (dateString.includes('/')) {
      const [day, month, year] = dateString.split('/');
      return new Date(`${year}-${month}-${day}`);
    } else if (dateString.includes('-')) {
      return new Date(dateString);
    } else {
      console.warn('Unknown date format:', dateString);
      return new Date(NaN);
    }
  }

  function titleCase(str) {
    if (!str) return '';
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(match) {
      return match.toUpperCase();
    });
  }

  function exportToExcel() {
    const button = document.getElementById('export-btn');
    const originalText = button.innerHTML;

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';

    const status = document.getElementById('status').value;
    const queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);

    fetch(`../backend/api/export_technicians_excel.php?${queryParams}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Erro na resposta do servidor');
        }
        return response.blob();
      })
      .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `technicians_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        button.disabled = false;
        button.innerHTML = originalText;
      })
      .catch(error => {
        console.error('Erro na exportação:', error);
        alert('Erro ao exportar para Excel. Tente novamente.');

        button.disabled = false;
        button.innerHTML = originalText;
      });
  }

  function exportToPDF() {
    const button = document.getElementById('export-pdf-btn');
    const originalText = button.innerHTML;

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';

    const status = document.getElementById('status').value;
    const queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);

    fetch(`../backend/api/export_technicians_pdf.php?${queryParams}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Erro na resposta do servidor');
        }
        return response.blob();
      })
      .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `technicians_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        button.disabled = false;
        button.innerHTML = originalText;
      })
      .catch(error => {
        console.error('Erro na exportação:', error);
        alert('Erro ao exportar PDF. Tente novamente.');

        button.disabled = false;
        button.innerHTML = originalText;
      });
  }
</script>

<?php include '../components/footer.php'; ?>