<?php
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

// Add this after the other PHP code at the top
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

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

$teachers = get_postg_docente($conn);
$courses = get_all_postg_courses($conn);

$_SESSION['user-data'] = $teachers;
?>

<style>
  .sortable {
    cursor: pointer;
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
    padding: 4px 10px;
    font-size: 12px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }

  .action-button:hover {
    background-color: #0056b3;
  }

  /* Status badge styles */
  .status-approved,
  .status-badge.status-approved {
    background-color: #d4edda;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    display: inline-block;
  }

  .status-not-approved,
  .status-badge.status-not-approved {
    background-color: #f8d7da;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    display: inline-block;
  }

  .status-pending,
  .status-badge.status-pending {
    background-color: #fff3cd;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    display: inline-block;
  }

  .discipline-status {
    margin-bottom: 5px;
  }

  .discipline-name {
    margin-right: 10px;
  }

  .table {
    table-layout: auto;
  }

  /* Nome */
  .table th:nth-child(1),
  .table td:nth-child(1) {
    width: 25%;
  }

  /* Email */
  .table th:nth-child(2),
  .table td:nth-child(2) {
    width: auto;
    max-width: 20%;
  }

  /* Data de Inscrição */
  .table th:nth-child(4),
  .table td:nth-child(4) {
    width: 8rem;
  }

  /* Data de chamada (when visible) */
  .table th:nth-child(5),
  .table td:nth-child(5) {
    width: 8rem;
  }

  /* Cursos */
  .table th:last-child,
  .table td:last-child {
    width: 25%;
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

  .form-control:focus {
    outline: none;
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
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

  /* Action button style update */
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
</style>

<div class="container">
  <a href="home.php" class="btn btn-info mt-5">← Voltar ao Início</a>
  <h1 class="main-title">Docentes / Assessoramento Técnico - Pós Graduação</h1>
  <div class="filter-container">
    <div class="filter-group">
      <label for="name">Filtrar por nome</label>
      <input type="text" name="name" id="name" placeholder="Digite o nome...">
    </div>
    <div class="filter-group">
      <label for="category">Filtrar por categoria</label>
      <select name="category" id="category">
        <option value=""></option>
        <option value="1">Docente</option>
        <option value="2">Docente Conteudista</option>
        <option value="3">Docente Assistente</option>
        <option value="9">Assessoramento Técnico</option>
      </select>
    </div>
    <div class="filter-group">
      <label for="course">Filtrar por cursos</label>
      <select name="course" id="course">
        <option value=""></option>
        <?php foreach ($courses as $course): ?>
          <option value="<?= $course['id'] ?>"
            title="<?= htmlspecialchars($course['name']) ?>">
            <?= htmlspecialchars(truncate_text($course['name'], 45)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label for="status">Filtrar por status</label>
      <select name="status" id="status">
        <option value=""></option>
        <option value="1">Apto</option>
        <option value="0">Inapto</option>
        <option value="null">Aguardando</option>
        <option value="no-disciplines">Sem disciplinas</option>
      </select>
    </div>
  </div>
  <div class="action-buttons mb-3">
    <button
      id="export-btn"
      class="btn btn-success"
      onclick="exportToExcel()"
      disabled>
      <i class="fas fa-file-excel"></i>
      Exportar para Excel
    </button>
    <button
      id="export-pdf-btn"
      class="btn btn-primary"
      onclick="exportToPDF()"
      disabled
      style="margin-left: 10px;">
      <i class="fas fa-file-pdf"></i>
      Exportar para PDF
    </button>
  </div>
  <div id="table-container" class="table-responsive">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th class="sortable" onclick="sortTable('name')">
            Nome
            <span class="sort-indicator" data-column="name"></span>
          </th>
          <th>Email</th>
          <th class="sortable" onclick="sortTable('date')">
            Data de Inscrição
            <span class="sort-indicator" data-column="date"></span>
          </th>
          <th id="called-at-header" style="display: none;">Data de chamada</th>
          <th>Cursos e Status</th>
        </tr>
      </thead>
      <tbody id="teachers-table-body">
        <?php
        function formatDate($dateString)
        {
          if (!$dateString) return '';
          $date = new DateTime($dateString);
          return $date->format('d/m/Y');
        }

        function parseStatusLabel($status)
        {
          if ($status === null || $status === 'null' || $status === '') {
            return 'Aguardando';
          } elseif ($status === '1' || $status === 1) {
            return 'Apto';
          } elseif ($status === '0' || $status === 0) {
            return 'Inapto';
          }
          return 'Aguardando';
        }

        function getStatusClass($status)
        {
          if ($status === null || $status === 'null' || $status === '') {
            return 'status-pending';
          } elseif ($status === '1' || $status === 1) {
            return 'status-approved';
          } elseif ($status === '0' || $status === 0) {
            return 'status-not-approved';
          }
          return 'status-pending';
        }

        foreach ($teachers as $teacher):
          $created_at_formatted = formatDate($teacher['created_at']);
        ?>
          <tr>
            <td><?= htmlspecialchars(titleCase($teacher['name'])) ?></td>
            <td><?= htmlspecialchars($teacher['email']) ?></td>
            <td><?= $created_at_formatted ?></td>
            <td class="called-at-cell" style="display: none;"></td>
            <td>
              <?php if (!empty($teacher['discipline_statuses'])): ?>
                <?php
                $disciplineGroups = explode('|~~|', $teacher['discipline_statuses']);
                foreach ($disciplineGroups as $group):
                  $parts = explode('|~|', $group);
                  if (count($parts) >= 3) {
                    $disciplineId = $parts[0];
                    $disciplineName = $parts[1];
                    $status = isset($parts[2]) ? $parts[2] : null;
                    $statusLabel = parseStatusLabel($status);
                    $statusClass = getStatusClass($status);
                ?>
                    <div class="discipline-status">
                      <span class="discipline-name"><?= htmlspecialchars($disciplineName) ?></span>
                      <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </div>
                <?php
                  }
                endforeach;
                ?>
              <?php else: ?>
                <span class="text-muted">Sem disciplinas</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Course Invitation Modal -->
<div id="invitationModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Enviar Convite para Curso</h2>
      <span class="close" onclick="closeInvitationModal()">&times;</span>
    </div>
    <div class="modal-body">
      <form id="invitationForm">
        <input type="hidden" id="teacherId" name="teacher_id">
        <input type="hidden" id="courseId" name="course_id">
        <input type="hidden" id="teacherEmail" name="teacher_email">
        <input type="hidden" id="isPostgraduate" name="is_postgraduate" value="">

        <div class="form-group">
          <label><strong>Professor:</strong></label>
          <p id="teacherName"></p>
        </div>

        <div class="form-group">
          <label><strong>Curso:</strong></label>
          <p id="courseName"></p>
        </div>

        <div class="form-group">
          <label for="messageSubject">Assunto:</label>
          <input type="text" id="messageSubject" name="subject" class="form-control"
            value="Convite para lecionar no curso" required>
        </div>

        <div class="form-group">
          <label for="messageBody">Mensagem:</label>
          <textarea id="messageBody" name="message" class="form-control" rows="6" required>
Prezado(a) Professor(a),

Gostaríamos de convidá-lo(a) para lecionar no curso mencionado acima.

Por favor, clique em um dos botões abaixo para aceitar ou recusar este convite.

Atenciosamente,
Coordenação de Cursos
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
  // Add at the very beginning of your script
  document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('course').value) {
      setTimeout(() => {
        invitationStatuses = {};
        fetchFilteredData();
      }, 100);
    }
  });
  let allTeachers = <?php echo json_encode($teachers); ?>;
  let currentTeachers = [...allTeachers];
  let currentSort = {
    column: null,
    direction: 'asc'
  };
  let isFilteredByCourse = false;
  const isAdmin = <?php echo json_encode($isAdmin); ?>;

  function hasActiveFilters() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    return !!(category || course || status || name);
  }
  let invitationStatuses = {}; // Cache for invitation statuses

  async function checkInvitationStatus(courseId) {
    const isPostgraduate = window.location.pathname.includes('docentes-pos');

    try {
      const response = await fetch(`../backend/api/check_course_invitation_status.php?course_id=${courseId}&is_postgraduate=${isPostgraduate}`);
      const data = await response.json();

      if (data.success) {
        invitationStatuses[courseId] = data;
        return data;
      }
    } catch (error) {
      console.error('Error checking invitation status:', error);
    }

    return null;
  }

  async function saveContractInfo(teacherId, courseId, contractInfo) {
    const isPostgraduate = window.location.pathname.includes('docentes-pos');
    const formData = new FormData();
    formData.append('teacher_id', teacherId);
    formData.append('course_id', courseId);
    formData.append('contract_info', contractInfo);
    formData.append('teacher_type', isPostgraduate ? 'postgraduate' : 'regular');

    try {
      const response = await fetch('../backend/api/save_contract_info.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      if (result.success) {
        alert('Informações contratuais salvas com sucesso!');
      } else {
        alert('Erro ao salvar informações: ' + result.message);
      }
    } catch (error) {
      console.error('Error saving contract info:', error);
      alert('Erro ao salvar informações contratuais');
    }
  }
  async function saveContractInfo(teacherId, courseId, contractInfo) {
    const isPostgraduate = window.location.pathname.includes('docentes-pos');
    const formData = new FormData();
    formData.append('teacher_id', teacherId);
    formData.append('course_id', courseId);
    formData.append('contract_info', contractInfo);
    formData.append('teacher_type', isPostgraduate ? 'postgraduate' : 'regular');

    try {
      const response = await fetch('../backend/api/save_contract_info.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      if (result.success) {
        alert('Informações contratuais salvas com sucesso!');
      } else {
        alert('Erro ao salvar informações: ' + result.message);
      }
    } catch (error) {
      console.error('Error saving contract info:', error);
      alert('Erro ao salvar informações contratuais');
    }
  }
  async function updateTable() {
    invitationStatuses = {};
    const tbody = document.querySelector('.table tbody');
    tbody.innerHTML = '';


    if (!currentTeachers || currentTeachers.length === 0) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = isAdmin ? 5 : 4;
      cell.textContent = 'Nenhum docente encontrado.';
      cell.style.textAlign = 'center';
      row.appendChild(cell);
      tbody.appendChild(row);

      // Disable export buttons when no data
      document.getElementById('export-btn').disabled = true;
      document.getElementById('export-pdf-btn').disabled = !hasActiveFilters();
      return;
    }

    // Only check invitation status if filtered by course
    if (isFilteredByCourse) {
      const courseSelect = document.getElementById('course');
      const selectedCourseId = courseSelect.value;

      if (selectedCourseId) {
        const invitationStatus = await checkInvitationStatus(selectedCourseId);
      }
    }
    if (isFilteredByCourse) {
      document.getElementById('called-at-header').style.display = '';
    } else {
      document.getElementById('called-at-header').style.display = 'none';
    }

    // Sort by called_at when filtered by course
    if (isFilteredByCourse) {
      currentTeachers.sort((a, b) => {
        // Check if either teacher has a pending invitation
        const courseSelect = document.getElementById('course');
        const selectedCourseId = courseSelect.value;
        const invitationStatus = invitationStatuses[selectedCourseId];

        let aPending = false;
        let bPending = false;

        if (invitationStatus && invitationStatus.pending_teachers) {
          aPending = invitationStatus.pending_teachers.includes(parseInt(a.id));
          bPending = invitationStatus.pending_teachers.includes(parseInt(b.id));
        }

        // If one is pending and the other isn't, pending goes to bottom
        if (aPending && !bPending) return 1;
        if (!aPending && bPending) return -1;

        // If both pending or both not pending, sort by called_at date
        let dateA = null;
        let dateB = null;

        if (a.discipline_statuses) {
          const disciplinesA = a.discipline_statuses.split('|~~|');
          for (const disc of disciplinesA) {
            const parts = disc.split('|~|');
            if (parts.length >= 4 && parts[3]) {
              const dateParts = parts[3].split('/');
              if (dateParts.length === 3) {
                const date = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                if (!dateA || date < dateA) dateA = date;
              }
            }
          }
        }

        if (b.discipline_statuses) {
          const disciplinesB = b.discipline_statuses.split('|~~|');
          for (const disc of disciplinesB) {
            const parts = disc.split('|~|');
            if (parts.length >= 4 && parts[3]) {
              const dateParts = parts[3].split('/');
              if (dateParts.length === 3) {
                const date = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                if (!dateB || date < dateB) dateB = date;
              }
            }
          }
        }

        if (!dateA) dateA = '9999-12-31';
        if (!dateB) dateB = '9999-12-31';

        return dateA.localeCompare(dateB);
      });
    }

    let isFirstInList = true;
    let invitationHandled = false;

    currentTeachers.forEach((teacher) => {
      const row = document.createElement('tr');

      // Make row clickable if admin
      if (isAdmin) {
        row.style.cursor = 'pointer';
        row.onclick = () => {
          // Use the appropriate page based on current location
          const targetPage = window.location.pathname.includes('docentes-pos') ? 'docente-pos.php' : 'docente.php';
          window.location.href = `${targetPage}?id=${teacher.id}`;
        };
      }

      // Name cell with invitation logic
      const nameCell = document.createElement('td');
      nameCell.textContent = teacher.name || '';

      // Handle course filter actions
      if (isFilteredByCourse) {
        const courseSelect = document.getElementById('course');
        const selectedCourseId = courseSelect.value;
        const selectedCourseName = courseSelect.options[courseSelect.selectedIndex].text;
        const invitationStatus = invitationStatuses[selectedCourseId];

        if (invitationStatus) {
          // Check if this teacher has a pending invitation
          const hasPendingInvitation = invitationStatus.pending_teachers &&
            invitationStatus.pending_teachers.includes(teacher.id);

          // Check if this teacher has an accepted invitation
          const isAcceptedTeacher = invitationStatus.accepted_teachers &&
            invitationStatus.accepted_teachers.hasOwnProperty(teacher.id);

          if (hasPendingInvitation) {
            // Show "Aguardando resposta" for pending teachers
            const pendingSpan = document.createElement('span');
            pendingSpan.className = 'invitation-pending';
            pendingSpan.textContent = 'Aguardando resposta';
            pendingSpan.style.marginLeft = '10px';
            nameCell.appendChild(pendingSpan);
          } else if (isAcceptedTeacher) {
            // Show contract textarea for any accepted teacher
            const contractDiv = document.createElement('div');
            contractDiv.style.display = 'inline-block';
            contractDiv.style.marginLeft = '10px';

            const label = document.createElement('label');
            label.className = 'contract-label';
            label.textContent = 'Contratado:';

            const textarea = document.createElement('textarea');
            textarea.className = 'contract-textarea';
            textarea.placeholder = 'Informações do contrato...';
            textarea.value = invitationStatus.accepted_teachers[teacher.id] || '';

            const saveBtn = document.createElement('button');
            saveBtn.className = 'contract-save-btn';
            saveBtn.textContent = 'Salvar';
            saveBtn.onclick = (e) => {
              e.stopPropagation();
              saveContractInfo(teacher.id, selectedCourseId, textarea.value);
            };

            contractDiv.appendChild(label);
            contractDiv.appendChild(textarea);
            contractDiv.appendChild(saveBtn);
            nameCell.appendChild(contractDiv);
          }

          // Handle invitation button only if not already handled and no pending invitation
          if (!invitationHandled && !hasPendingInvitation && !isAcceptedTeacher) {
            if (invitationStatus.can_send_next && isFirstInList) {
              // Show send invitation button
              const actionButton = document.createElement('button');
              actionButton.className = 'action-button';
              actionButton.textContent = 'Enviar Convite';

              actionButton.onclick = (e) => {
                e.stopPropagation();
                openInvitationModal(
                  teacher.id,
                  teacher.name,
                  teacher.email,
                  selectedCourseId,
                  selectedCourseName
                );
              };

              nameCell.appendChild(actionButton);
              invitationHandled = true;
            }
          }
        } else if (!invitationHandled && isFirstInList) {
          // No invitation sent yet, show button for first teacher
          const actionButton = document.createElement('button');
          actionButton.className = 'action-button';
          actionButton.textContent = 'Enviar Convite';

          actionButton.onclick = (e) => {
            e.stopPropagation();
            openInvitationModal(
              teacher.id,
              teacher.name,
              teacher.email,
              selectedCourseId,
              selectedCourseName
            );
          };

          nameCell.appendChild(actionButton);
          invitationHandled = true;
        }

        isFirstInList = false;
      }

      row.appendChild(nameCell);

      // Email
      const emailCell = document.createElement('td');
      emailCell.textContent = teacher.email || '';
      row.appendChild(emailCell);

      // Created at
      const createdCell = document.createElement('td');
      createdCell.textContent = formatDate(teacher.created_at);
      row.appendChild(createdCell);

      // Called at (only when filtered by course)
      const calledCell = document.createElement('td');
      calledCell.className = 'called-at-cell';

      if (isFilteredByCourse && teacher.discipline_statuses) {
        // Extract called_at from filtered discipline
        const disciplines = teacher.discipline_statuses.split('|~~|');
        let earliestDate = null;

        disciplines.forEach(disc => {
          const parts = disc.split('|~|');
          if (parts.length >= 4 && parts[3]) {
            if (!earliestDate || parts[3] < earliestDate) {
              earliestDate = parts[3];
            }
          }
        });

        calledCell.textContent = earliestDate || '';
        calledCell.style.display = '';
      } else {
        calledCell.style.display = 'none';
      }

      row.appendChild(calledCell);

      // Disciplines
      const disciplinesCell = document.createElement('td');
      if (teacher.discipline_statuses) {
        const disciplineGroups = teacher.discipline_statuses.split('|~~|');
        disciplineGroups.forEach(group => {
          const parts = group.split('|~|');
          if (parts.length >= 3) {
            const disciplineId = parts[0];
            const disciplineName = parts[1];
            const status = parts[2];

            const div = document.createElement('div');
            div.className = 'discipline-status';

            const nameSpan = document.createElement('span');
            nameSpan.className = 'discipline-name';
            nameSpan.textContent = disciplineName;

            const statusSpan = document.createElement('span');
            statusSpan.className = `status-badge ${getStatusClass(status)}`;
            statusSpan.textContent = parseStatusLabel(status);

            div.appendChild(nameSpan);
            div.appendChild(statusSpan);
            disciplinesCell.appendChild(div);
          }
        });
      } else {
        disciplinesCell.innerHTML = '<span class="text-muted">Sem disciplinas</span>';
      }
      row.appendChild(disciplinesCell);

      tbody.appendChild(row);
    });

    // Update export button states
    const hasData = currentTeachers.length > 0;
    document.getElementById('export-btn').disabled = !hasData;

    // PDF export is only enabled when filters are active
    const hasFilters = hasActiveFilters();
    document.getElementById('export-pdf-btn').disabled = !hasFilters;
  }
  setInterval(async () => {
    if (isFilteredByCourse) {
      const courseSelect = document.getElementById('course');
      if (courseSelect.value) {
        // Clear cache to force fresh check
        invitationStatuses = {};
        await updateTable();
      }
    }
  }, 60000);


  function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
  }

  function parseStatusLabel(status) {
    if (status === null || status === 'null' || status === '') {
      return 'Aguardando';
    } else if (status === '1' || status === 1) {
      return 'Apto';
    } else if (status === '0' || status === 0) {
      return 'Inapto';
    }
    return 'Aguardando';
  }

  function getStatusClass(status) {
    if (status === null || status === 'null' || status === '') {
      return 'status-pending';
    } else if (status === '1' || status === 1) {
      return 'status-approved';
    } else if (status === '0' || status === 0) {
      return 'status-not-approved';
    }
    return 'status-pending';
  }

  function sortTable(column) {
    if (currentSort.column === column) {
      currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      currentSort.column = column;
      currentSort.direction = 'asc';
    }

    updateSortIndicators();
    sortTeachers();
    updateTable();
  }

  function updateSortIndicators() {
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
      indicator.textContent = '';
      indicator.classList.remove('active');
    });

    if (currentSort.column) {
      const activeIndicator = document.querySelector(`.sort-indicator[data-column="${currentSort.column}"]`);
      activeIndicator.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
      activeIndicator.classList.add('active');
    }
  }

  function sortTeachers() {
    currentTeachers.sort((a, b) => {
      let compareResult = 0;

      if (currentSort.column === 'name') {
        const nameA = (a.name || '').trim();
        const nameB = (b.name || '').trim();

        compareResult = nameA.localeCompare(nameB, 'pt-BR', {
          numeric: true,
          sensitivity: 'accent'
        });

      } else if (currentSort.column === 'date') {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        compareResult = dateA - dateB;
      }

      return currentSort.direction === 'asc' ? compareResult : -compareResult;
    });
  }

  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    // Check if filtering by course
    isFilteredByCourse = course !== '';

    if (isFilteredByCourse) {
      document.getElementById('called-at-header').style.display = '';
    } else {
      document.getElementById('called-at-header').style.display = 'none';
    }

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status && status !== 'no-disciplines') queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    fetch('../backend/api/get_filtered_teachers_postg.php?' + queryParams)
      .then(response => response.json())
      .then(data => {
        if (status === 'no-disciplines') {
          currentTeachers = allTeachers.filter(t => !t.discipline_statuses || t.discipline_statuses === '');
        } else {
          currentTeachers = data;
        }
        updateTable();
      })
      .catch(error => {
        console.error('Error:', error);
        currentTeachers = [];
        updateTable();
      });
  }

  // Add event listeners
  document.getElementById('category').addEventListener('change', fetchFilteredData);
  document.getElementById('course').addEventListener('change', fetchFilteredData);
  document.getElementById('status').addEventListener('change', fetchFilteredData);
  document.getElementById('name').addEventListener('input', fetchFilteredData);

  // Export to Excel function
  function exportToExcel() {
    const button = document.getElementById('export-btn');
    const originalText = button.innerHTML;

    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';

    // Get current filters
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    // Call the backend API
    fetch(`../backend/api/export_docentes_pos_excel.php?${queryParams}`)
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
        a.download = `docentes_${new Date().toISOString().split('T')[0]}.csv`;
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

  // Export to PDF function
  function exportToPDF() {
    const button = document.getElementById('export-pdf-btn');
    const originalText = button.innerHTML;

    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';

    // Get current filters
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    // Call the backend API
    fetch(`../backend/api/export_docentes_pos_pdf.php?${queryParams}`)
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
        a.download = `docentes_${new Date().toISOString().split('T')[0]}.pdf`;
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
  // Initialize the table with click handlers
  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('export-btn').disabled = true;
    document.getElementById('export-pdf-btn').disabled = true;
    updateTable();
  });
  // Modal functions
  function openInvitationModal(teacherId, teacherName, teacherEmail, courseId, courseName) {
    document.getElementById('teacherId').value = teacherId;
    document.getElementById('teacherEmail').value = teacherEmail;
    document.getElementById('courseId').value = courseId;
    document.getElementById('teacherName').textContent = teacherName;
    document.getElementById('courseName').textContent = courseName;

    // Determine if it's postgraduate based on current page
    const isPostgraduate = window.location.pathname.includes('docentes-pos');
    document.getElementById('isPostgraduate').value = isPostgraduate ? '1' : '0';

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

  // Replace the form submission handler with this updated version:

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

        // Clear cache and refresh the table to show the pending status
        invitationStatuses = {};
        await fetchFilteredData();
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

  // Update the action button onclick to include course information
  function handleActionButton(teacher, courseId, courseName) {
    openInvitationModal(
      teacher.id,
      teacher.name,
      teacher.email,
      courseId,
      courseName
    );
  }
</script>

<?php require_once '../components/footer.php'; ?>