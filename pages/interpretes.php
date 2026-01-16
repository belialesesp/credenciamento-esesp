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

// Get initial data - will be replaced by AJAX
$interpreters = get_interpreters($conn);
$_SESSION['user-data'] = $interpreters;

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

  .filter-group select,
  .filter-group input {
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

  .interpreter-row {
    cursor: pointer;
  }

  .interpreter-row:hover {
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

  .filter-stats {
    margin-top: 10px;
    font-size: 14px;
    color: #666;
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

  /* Invitation styles */
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
</style>

<div class="container">
  <a href="home.php" class="btn btn-info mt-5">← Voltar ao Início</a>
  <h1 class="main-title">Intérpretes</h1>

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

  <div class="filter-stats" id="filterStats"></div>

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
    <tbody id="interpretersTableBody">
      <!-- Table will be populated by JavaScript -->
    </tbody>
  </table>
</div>

<!-- Interpreter Invitation Modal -->
<div id="invitationModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Enviar Convite para Intérprete</h2>
      <span class="close" onclick="closeInvitationModal()">&times;</span>
    </div>
    <div class="modal-body">
      <form id="invitationForm">
        <input type="hidden" id="interpreterId" name="user_id">
        <input type="hidden" id="interpreterEmail" name="teacher_email">
        <input type="hidden" name="user_type" value="interpreter">
        <input type="hidden" name="is_staff" value="true">

        <div class="form-group">
          <label><strong>Intérprete:</strong></label>
          <p id="interpreterName"></p>
        </div>

        <div class="form-group">
          <label for="messageSubject">Assunto:</label>
          <input type="text" id="messageSubject" name="subject" class="form-control"
            value="Convite para trabalho como intérprete" required>
        </div>

        <div class="form-group">
          <label for="messageBody">Mensagem:</label>
          <textarea id="messageBody" name="message" class="form-control" rows="6" required>
            Prezado(a) Intérprete,

            Gostaríamos de convidá-lo(a) para trabalhar conosco como intérprete.

            Por favor, clique em um dos botões abaixo para aceitar ou recusar este convite.

            Atenciosamente,
            Coordenação
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
  // Always add these at the top:
  const userRoles = <?php echo json_encode($_SESSION['user_roles'] ?? []); ?>;

  function canSendInvites() {
    return userRoles.includes('admin') || userRoles.includes('gedth');
  }

  function canViewContractInfo() {
    return userRoles.includes('admin') || userRoles.includes('gese');
  }

  // For page access check:
  const isAdmin = <?= json_encode(isAdministrativeRole()) ?>;

  // Function to check if user can send invites (admin or GEDTH only)
  function canSendInvites() {
    return userRoles.includes('admin') || userRoles.includes('gedth');
  }

  // Function to check if user can view/edit contract info (admin or GESE only)
  function canViewContractInfo() {
    return userRoles.includes('admin') || userRoles.includes('gese');
  }

  // Global variables for sorting
  let allInterpreters = <?= json_encode($interpreters) ?>;
  let currentInterpreters = [...allInterpreters];
  let currentSort = {
    column: 'called',
    direction: 'desc'
  };
  let invitationStatuses = {};

  // Modal functions
  function openInvitationModal(userId, userName, userEmail) {
    document.getElementById('interpreterId').value = userId;
    document.getElementById('interpreterEmail').value = userEmail;
    document.getElementById('interpreterName').textContent = userName;
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

        // Clear cache completely and refresh the data - LIKE TECHNICIANS
        invitationStatuses = {};

        // Force refresh of the data - LIKE TECHNICIANS
        await fetchFilteredData();

        // Force re-render the table - LIKE TECHNICIANS
        await renderTable(currentInterpreters);

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

  async function checkInvitationStatus(interpreterId) {
    try {
      const response = await fetch(`../backend/api/check_course_invitation_status.php?user_id=${interpreterId}&is_staff=true`);
      const data = await response.json();

      if (data.success) {
        return data;
      }
    } catch (error) {
      console.error('Error checking invitation status:', error);
    }
    return null;
  }

  async function saveContractInfo(userId, contractInfo) {
    try {
      const formData = new FormData();
      formData.append('teacher_id', userId); // Note: using teacher_id as expected by backend
      formData.append('contract_info', contractInfo);
      formData.append('is_staff', 'true');

      const response = await fetch('../backend/api/save_contract_info.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert('Informações do contrato salvas com sucesso!');
        // Refresh the table to show updated information
        await fetchFilteredData();
      } else {
        alert('Erro ao salvar informações do contrato: ' + result.message);
      }
    } catch (error) {
      console.error('Error saving contract info:', error);
      alert('Erro ao salvar informações do contrato.');
    }
  }

  // Load initial data
  document.addEventListener('DOMContentLoaded', function() {
    // Set up sort click handlers
    document.querySelectorAll('.sortable').forEach(th => {
      th.addEventListener('click', () => {
        const sortColumn = th.getAttribute('data-sort');
        handleSort(sortColumn);
      });
    });

    // Set up filter handler
    document.getElementById('status').addEventListener('change', filterInterpreters);

    // Load data and render table - ONLY call fetchFilteredData
    fetchFilteredData();

    // Show that we're sorted by called_at by default
    const calledHeader = document.querySelector('th:nth-child(4)');
    if (calledHeader) {
      calledHeader.innerHTML = 'Data de Chamada <span style="font-size: 12px;">↓</span>';
    }
  });


  // Sorting function
  function handleSort(column) {
    // If clicking the same column, toggle direction
    if (currentSort.column === column) {
      currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      // New column, default to ascending
      currentSort.column = column;
      currentSort.direction = 'asc';
    }

    // Update sort indicators
    updateSortIndicators();

    // Sort and re-render
    sortInterpreters();
    renderTable(currentInterpreters);
  }

  function updateSortIndicators() {
    // Clear all indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
      indicator.textContent = '';
      indicator.classList.remove('active');
    });

    // Set active indicator
    const activeIndicator = document.getElementById(`sort-${currentSort.column}`);
    if (activeIndicator) {
      activeIndicator.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
      activeIndicator.classList.add('active');
    }
  }

  function sortInterpreters() {
    currentInterpreters.sort((a, b) => {
      // Priority 1: Users without called_at come first
      const hasCalledAtA = a.called_at && a.called_at.trim() !== '';
      const hasCalledAtB = b.called_at && b.called_at.trim() !== '';

      if (!hasCalledAtA && hasCalledAtB) return -1; // A comes first (no called_at)
      if (hasCalledAtA && !hasCalledAtB) return 1; // B comes first (no called_at)

      // Priority 2: If both have called_at, sort by called_at date (oldest first)
      if (hasCalledAtA && hasCalledAtB) {
        const dateA = parseDate(a.called_at);
        const dateB = parseDate(b.called_at);
        return dateA - dateB; // Oldest first
      }

      // Priority 3: If neither has called_at, sort by creation date (oldest first)
      const createdA = new Date(a.created_at);
      const createdB = new Date(b.created_at);
      return createdA - createdB; // Oldest first
    });
  }

  async function fetchFilteredData() {


    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    const queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    try {
      const response = await fetch('../backend/api/get_filtered_interpreters.php?' + queryParams.toString());
      const data = await response.json();


      // Store the data
      allInterpreters = data;
      currentInterpreters = [...data];

      // Re-sort and re-render with the new data
      sortInterpreters();
      renderTable(currentInterpreters);
      updateStats();
    } catch (error) {
      console.error('Error:', error);
    }
  }

  function filterInterpreters() {
    const statusFilter = document.getElementById('status').value;
    const nameFilter = document.getElementById('name').value.toLowerCase();

    if (!statusFilter && !nameFilter) {
      currentInterpreters = [...allInterpreters];
    } else {
      currentInterpreters = allInterpreters.filter(i => {
        // Status filter
        const statusMatch = !statusFilter ||
          (statusFilter === 'null' ? (i.enabled === null || i.enabled === '') :
            String(i.enabled) === statusFilter);

        // Name filter
        const nameMatch = !nameFilter ||
          (i.name && i.name.toLowerCase().includes(nameFilter));

        return statusMatch && nameMatch;
      });
    }

    sortInterpreters();
    renderTable(currentInterpreters);
    updateStats();
  }

  // Add event listener for the name input
  document.getElementById('name').addEventListener('input', function() {
    clearTimeout(window.nameFilterTimeout);
    window.nameFilterTimeout = setTimeout(fetchFilteredData, 300);
  });

  async function renderTable(interpreters) {

    const tbody = document.getElementById('interpretersTableBody');
    tbody.innerHTML = ''; // Clear table completely

    if (interpreters.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum intérprete encontrado</td></tr>';
      return;
    }

    // Sort interpreters by priority: no called_at first, then by oldest created_at
    const sortedInterpreters = [...interpreters].sort((a, b) => {
      // Priority 1: Users without called_at come first
      const hasCalledAtA = a.called_at && a.called_at.trim() !== '';
      const hasCalledAtB = b.called_at && b.called_at.trim() !== '';

      if (!hasCalledAtA && hasCalledAtB) return -1; // A comes first (no called_at)
      if (hasCalledAtA && !hasCalledAtB) return 1; // B comes first (no called_at)

      // Priority 2: If both have no called_at, sort by creation date (oldest first)
      if (!hasCalledAtA && !hasCalledAtB) {
        const createdA = new Date(a.created_at);
        const createdB = new Date(b.created_at);
        return createdA - createdB; // Oldest first
      }

      // Priority 3: If both have called_at, sort by called_at date (oldest first)
      const dateA = parseDate(a.called_at);
      const dateB = parseDate(b.called_at);
      return dateA - dateB; // Oldest first
    });

    // Check invitation status for all interpreters first
    const interpreterInvitationStatuses = {};
    for (const interpreter of sortedInterpreters) {
      if (interpreter.enabled == 1) { // Only check for "Apto" interpreters
        interpreterInvitationStatuses[interpreter.id] = await checkInvitationStatus(interpreter.id);
      }
    }

    // Find the interpreter who should get the invite button
    let interpreterToInvite = null;
    for (const interpreter of sortedInterpreters) {
      // Only consider "Apto" interpreters
      if (interpreter.enabled != 1) continue;

      const invitationStatus = interpreterInvitationStatuses[interpreter.id];

      if (!invitationStatus ||
        (!invitationStatus.has_pending &&
          !invitationStatus.is_accepted &&
          !invitationStatus.is_rejected)) {
        interpreterToInvite = interpreter;
        break;
      }
    }
    // Now render using the sorted interpreters array
    for (const interpreter of sortedInterpreters) {
      const enabled = interpreter.enabled == 1 ? 'Apto' :
        interpreter.enabled == 0 ? 'Inapto' : 'Aguardando';

      const statusClass = interpreter.enabled == 1 ? 'status-approved' :
        interpreter.enabled == 0 ? 'status-not-approved' : 'status-pending';

      // Format dates
      const createdDate = new Date(interpreter.created_at);
      const createdDateF = createdDate.toLocaleDateString('pt-BR') + ' ' +
        createdDate.toLocaleTimeString('pt-BR', {
          hour: '2-digit',
          minute: '2-digit'
        });

      // Date formatting
      let calledDateF = '-';
      if (interpreter.called_at) {
        const calledDate = parseDate(interpreter.called_at);
        if (!isNaN(calledDate.getTime())) {
          calledDateF = calledDate.toLocaleDateString('pt-BR');
        } else {
          calledDateF = 'Data inválida';
          console.warn('Invalid date format:', interpreter.called_at);
        }
      }

      // Create row element
      const row = document.createElement('tr');
      row.className = 'interpreter-row';
      row.style.cursor = 'pointer';

      // Name cell with invitation status
      const nameCell = document.createElement('td');
      nameCell.textContent = titleCase(interpreter.name);

      // Get invitation status from our pre-fetched data
      const invitationStatus = interpreterInvitationStatuses[interpreter.id];

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

          // Only show contract textarea if user can view contract info
          if (canViewContractInfo()) {
            // Add contract textarea for accepted staff
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
        } else if (canSendInvites() && interpreter.enabled == 1) {

          const inviteBtn = document.createElement('button');
          inviteBtn.className = 'action-button';
          inviteBtn.textContent = 'Enviar Convite';
          inviteBtn.onclick = (e) => {
            e.stopPropagation();
            openInvitationModal(interpreter.id, interpreter.name, interpreter.email);
          };
          nameCell.appendChild(inviteBtn);
        }
      } else if (canSendInvites() && interpreter.enabled == 1) {
        // No invitation status, show button for APTO
        const inviteBtn = document.createElement('button');
        inviteBtn.className = 'action-button';
        inviteBtn.textContent = 'Enviar Convite';
        inviteBtn.onclick = (e) => {
          e.stopPropagation();
          openInvitationModal(interpreter.id, interpreter.name, interpreter.email);
        };
        nameCell.appendChild(inviteBtn);
      }

      row.appendChild(nameCell);

      // Email cell
      const emailCell = document.createElement('td');
      emailCell.textContent = interpreter.email.toLowerCase();
      row.appendChild(emailCell);

      // Created at cell
      const createdCell = document.createElement('td');
      createdCell.textContent = createdDateF;
      row.appendChild(createdCell);

      // Called at cell
      const calledCell = document.createElement('td');
      calledCell.textContent = calledDateF;
      row.appendChild(calledCell);

      // Status cell
      const statusCell = document.createElement('td');
      const statusSpan = document.createElement('span');
      statusSpan.className = statusClass;
      statusSpan.textContent = enabled;
      statusCell.appendChild(statusSpan);
      row.appendChild(statusCell);

      // Click handler for row
      row.onclick = (e) => {
        if (e.target.closest('button, textarea, input')) {
          return;
        }
        window.location.href = `interprete.php?id=${interpreter.id}`;
      };

      tbody.appendChild(row);
    }

  }

  function updateStats() {
    const statsDiv = document.getElementById('filterStats');
    const total = currentInterpreters.length;
    const statusFilter = document.getElementById('status').value;

    if (statusFilter && total > 0) {
      const statusText = statusFilter === '1' ? 'aptos' :
        statusFilter === '0' ? 'inaptos' : 'aguardando aprovação';
      statsDiv.textContent = `Mostrando ${total} intérprete${total !== 1 ? 's' : ''} ${statusText}`;
    } else if (total > 0) {
      statsDiv.textContent = `Total: ${total} intérprete${total !== 1 ? 's' : ''}`;
    } else {
      statsDiv.textContent = '';
    }
  }

  // Helper function to parse dates in different formats
  function parseDate(dateString) {
    if (!dateString) return new Date(NaN);

    if (dateString.includes('/')) {
      // Handle "DD/MM/YYYY" format
      const [day, month, year] = dateString.split('/');
      return new Date(`${year}-${month}-${day}`);
    } else if (dateString.includes('-')) {
      // Handle "YYYY-MM-DD" or "YYYY-MM-DD HH:MM:SS" format
      return new Date(dateString);
    } else {
      console.warn('Unknown date format:', dateString);
      return new Date(NaN);
    }
  }

  // Title case function
  function titleCase(str) {
    if (!str) return '';
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(match) {
      return match.toUpperCase();
    });
  }

  function exportToExcel() {
    const button = document.getElementById('export-btn');
    const originalText = button.innerHTML;

    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';

    // Get current filters
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    const queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    // Call the backend API
    fetch(`../backend/api/export_interpreters_excel.php?${queryParams}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Erro na resposta do servidor');
        }
        return response.blob();
      })
      .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `interpreters_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
      })
      .catch(error => {
        console.error('Erro na exportação:', error);
        alert('Erro ao exportar para Excel. Tente novamente.');

        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
      });
  }

  function exportToPDF() {
    const button = document.getElementById('export-pdf-btn');
    const originalText = button.innerHTML;

    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';

    // Get current filters
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    const queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    // Call the backend API
    fetch(`../backend/api/export_interpreters_pdf.php?${queryParams}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Erro na resposta do servidor');
        }
        return response.blob();
      })
      .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `interpreters_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
      })
      .catch(error => {
        console.error('Erro na exportação:', error);
        alert('Erro ao exportar PDF. Tente novamente.');

        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
      });
  }
</script>

<?php include '../components/footer.php'; ?>