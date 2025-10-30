<?php
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

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

$teachers = get_docente($conn);
$courses = get_all_courses($conn);

$_SESSION['user-data'] = $teachers;
?>

<style>
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

  .table-hover tbody tr {
    cursor: pointer;
  }

  .table-hover tbody tr:hover {
    background-color: #f5f5f5;
  }

  /* Status badge styles */
  .status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 500;
    white-space: nowrap;
  }

  .status-pending {
    background-color: #ffc107;
    color: #212529;
  }

  .status-approved {
    background-color: #28a745;
    color: white;
  }

  .status-not-approved {
    background-color: #dc3545;
    color: white;
  }

  .discipline-status {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 5px;
    padding: 5px 0;
  }

  .discipline-name {
    display: flex;
    align-items: center;
    flex: 1;
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

  .discipline-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    padding: 6px 8px;
    background: #f5f5f5;
    border-radius: 4px;
  }

  .discipline-name {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
  }

  .activity-badge {
    display: inline-block;
    padding: 2px 8px;
    background: #3498db;
    color: white;
    font-size: 11px;
    font-weight: 600;
    border-radius: 12px;
    white-space: nowrap;
  }

  .status-approved {
    color: #27ae60;
    font-weight: 600;
    padding: 2px 8px;
    background: #d5f4e6;
    border-radius: 4px;
  }

  .status-not-approved {
    color: #e74c3c;
    font-weight: 600;
    padding: 2px 8px;
    background: #fadbd8;
    border-radius: 4px;
  }

  .status-pending {
    color: #f39c12;
    font-weight: 600;
    padding: 2px 8px;
    background: #fef5e7;
    border-radius: 4px;
  }

  .no-disciplines {
    color: #95a5a6;
    font-style: italic;
  }

  .called-date {
    font-size: 12px;
    color: #7f8c8d;
  }

  @media (max-width: 768px) {
    .discipline-status {
      flex-direction: column;
      align-items: flex-start;
    }

    .discipline-name {
      margin-bottom: 5px;
    }

    .activity-badge {
      margin-top: 5px;
      margin-left: 0;
    }
  }
</style>

<div class="container">
  <a href="home.php" class="btn btn-info mt-5">← Voltar ao Início</a>
  <h1 class="main-title">Docentes</h1>
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
        <option value="4">Coordenador Técnico</option>
        <option value="5">Conferencista / Palestrante</option>
        <option value="6">Painelista / Debatedor</option>
        <option value="7">Moderador</option>
        <option value="8">Reunião Técnica</option>
        <option value="9">Assessoramento Técnico</option>
        <option value="10">Revisão de Texto</option>
        <option value="11">Entrevista</option>
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
          <th>Nome</th>
          <th>Email</th>
          <th>Data de Inscrição</th>
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
                  $partCount = count($parts);

                  if ($partCount >= 5) {
                    $disciplineId = $parts[0];
                    $disciplineName = $parts[1];
                    $activityName = $parts[2];
                    $status = $parts[3];
                    $calledAt = $parts[4];
                    if (empty(trim($disciplineName))) {
                      continue;
                    }


                    $statusLabel = parseStatusLabel($status);
                    $statusClass = getStatusClass($status);
                ?>
                    <div class="discipline-status">
                      <span class="discipline-name">
                        <?= htmlspecialchars($disciplineName) ?>
                        <?php if (!empty(trim($activityName))): ?>
                          <span class="activity-badge"><?= htmlspecialchars($activityName) ?></span>
                        <?php endif; ?>
                      </span>
                      <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                      <?php if (!empty($calledAt) && $calledAt !== '00/00/0000'): ?>
                        <span class="called-date" data-called-at="<?= $calledAt ?>" style="display: none;"><?= $calledAt ?></span>
                      <?php endif; ?>
                    </div>
                <?php
                  }
                endforeach;
                ?>
              <?php else: ?>
                <span class="no-disciplines">Sem disciplinas</span>
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
        <input type="hidden" id="teacherId" name="user_id">
        <input type="hidden" id="courseId" name="course_id">
        <input type="hidden" id="teacherEmail" name="teacher_email">
        <input type="hidden" id="teacherType" name="teacher_type">

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
  let allTeachers = <?php echo json_encode($teachers); ?>;
  let currentTeachers = [...allTeachers];
  let isFilteredByCourse = false;

  function hasActiveFilters() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    return !!(category || course || status || name);
  }

  let invitationStatuses = {};
  // Add this function to get hours passed from sent_at date
  function getHoursPassedFromDate(sentAtDate) {
    if (!sentAtDate) return null;

    const sentDate = new Date(sentAtDate);
    const now = new Date();
    const hoursPassed = Math.floor((now - sentDate) / (1000 * 60 * 60));

    return hoursPassed;
  }
  // Updated checkInvitationStatus function with better error handling

  async function checkInvitationStatus(courseId) {
    const isPostgraduate = window.location.pathname.includes('docentes-pos');

    try {
      // Add timestamp to prevent caching
      const timestamp = new Date().getTime();
      const response = await fetch(`../backend/api/check_course_invitation_status.php?course_id=${courseId}&is_postgraduate=${isPostgraduate}&_t=${timestamp}`);
      const data = await response.json();

      if (data.success) {
        // Handle expired invitations - treat them as rejected
        if (data.expired_teachers && data.expired_teachers.length > 0) {
          // Move expired teachers to rejected list if not already there
          if (!data.rejected_teachers) {
            data.rejected_teachers = [];
          }

          data.expired_teachers.forEach(teacherId => {
            if (!data.rejected_teachers.includes(teacherId)) {
              data.rejected_teachers.push(teacherId);
            }
          });
        }

        return data;
      }
    } catch (error) {
      console.error('Error checking invitation status:', error);
    }

    return null;
  }


  async function saveContractInfo(teacherId, courseId, contractInfo) {
    try {
      const formData = new FormData();
      formData.append('teacher_id', teacherId);
      formData.append('course_id', courseId);
      formData.append('contract_info', contractInfo);
      formData.append('teacher_type', 'regular');

      const response = await fetch('../backend/api/save_contract_info.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert('Informações do contrato salvas com sucesso!');
        // Clear cache and refresh the table
        invitationStatuses = {};
        await updateTable();
      } else {
        alert('Erro ao salvar informações do contrato: ' + result.message);
      }
    } catch (error) {
      console.error('Error saving contract info:', error);
      alert('Erro ao salvar informações do contrato.');
    }
  }


  let isRendering = false;


  async function updateTable() {
    if (isRendering) {
      return;
    }

    isRendering = true;

    try {

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

        document.getElementById('export-btn').disabled = true;
        document.getElementById('export-pdf-btn').disabled = !hasActiveFilters();
        return;
      }

      let invitationStatus = null;
      if (isFilteredByCourse) {
        const courseSelect = document.getElementById('course');
        const selectedCourseId = courseSelect.value;

        if (selectedCourseId) {
          invitationStatus = await checkInvitationStatus(selectedCourseId);
          invitationStatuses[selectedCourseId] = invitationStatus;
        }
      }

      const calledAtHeader = document.getElementById('called-at-header');
      if (isFilteredByCourse) {
        calledAtHeader.style.display = '';
      } else {
        calledAtHeader.style.display = 'none';
      }

      let sortedTeachers = [...currentTeachers];

      // Keep existing sorting logic
      if (isFilteredByCourse && invitationStatus) {
        sortedTeachers.sort((a, b) => {
          const aId = parseInt(a.id);
          const bId = parseInt(b.id);

          const aPending = invitationStatus.pending_teachers ?
            invitationStatus.pending_teachers.some(id => parseInt(id) === aId) : false;
          const bPending = invitationStatus.pending_teachers ?
            invitationStatus.pending_teachers.some(id => parseInt(id) === bId) : false;

          const aRejected = invitationStatus.rejected_teachers ?
            invitationStatus.rejected_teachers.some(id => parseInt(id) === aId) : false;
          const bRejected = invitationStatus.rejected_teachers ?
            invitationStatus.rejected_teachers.some(id => parseInt(id) === bId) : false;

          const aExpired = invitationStatus.expired_teachers ?
            invitationStatus.expired_teachers.some(id => parseInt(id) === aId) : false;
          const bExpired = invitationStatus.expired_teachers ?
            invitationStatus.expired_teachers.some(id => parseInt(id) === bId) : false;

          const aAccepted = invitationStatus.accepted_teachers ?
            invitationStatus.accepted_teachers.hasOwnProperty(aId.toString()) : false;
          const bAccepted = invitationStatus.accepted_teachers ?
            invitationStatus.accepted_teachers.hasOwnProperty(bId.toString()) : false;


          const aIneligible = aPending || aRejected || aExpired || aAccepted;
          const bIneligible = bPending || bRejected || bExpired || bAccepted;

          if (!aIneligible && bIneligible) return -1;
          if (aIneligible && !bIneligible) return 1;

          let dateA = '9999-12-31';
          let dateB = '9999-12-31';

          if (a.discipline_statuses) {
            const disciplinesA = a.discipline_statuses.split('|~~|');
            for (const disc of disciplinesA) {
              const parts = disc.split('|~|');
              if (parts.length >= 5 && parts[4]) {
                const dateParts = parts[4].split('/');
                if (dateParts.length === 3) {
                  const date = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                  if (date < dateA) dateA = date;
                }
              }
            }
          }

          if (b.discipline_statuses) {
            const disciplinesB = b.discipline_statuses.split('|~~|');
            for (const disc of disciplinesB) {
              const parts = disc.split('|~|');
              if (parts.length >= 5 && parts[4]) {
                const dateParts = parts[4].split('/');
                if (dateParts.length === 3) {
                  const date = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                  if (date < dateB) dateB = date;
                }
              }
            }
          }

          return dateA.localeCompare(dateB);
        });
      }

      // Render teachers
      for (let i = 0; i < sortedTeachers.length; i++) {
        const teacher = sortedTeachers[i];

        const row = document.createElement('tr');
        row.dataset.teacherId = teacher.id;

        row.addEventListener('click', function(e) {
          if (e.target.closest('button, textarea, input, a')) {
            return;
          }
          const targetPage = window.location.pathname.includes('docentes-pos') ?
            'docente-pos.php' : 'docente.php';
          window.location.href = `${targetPage}?id=${teacher.id}`;
        });

        const nameCell = document.createElement('td');
        nameCell.textContent = teacher.name || '';

        if (isFilteredByCourse && invitationStatus) {
          const courseSelect = document.getElementById('course');
          const selectedCourseId = courseSelect.value;
          const selectedCourseName = courseSelect.options[courseSelect.selectedIndex].text;

          const teacherId = parseInt(teacher.id);

          // NEW: Check if teacher is "apto" for the selected course
          let hasAptoStatus = false;
          if (teacher.discipline_statuses) {
            const disciplines = teacher.discipline_statuses.split('|~~|');
            disciplines.forEach(disc => {
              const parts = disc.split('|~|');
              if (parts.length >= 5) {
                const disciplineId = parts[0];
                const disciplineName = parts[1];
                const activityName = parts[2];
                const status = parts[3];
                const calledAt = parts[4];

                // Check if this teacher is "apto" (status = 1) for the selected course
                if (disciplineId == selectedCourseId && (status === '1' || status === 1)) {
                  hasAptoStatus = true;
                }
              }
            });
          }

          const hasPendingInvitation = invitationStatus.pending_teachers ?
            invitationStatus.pending_teachers.some(id => parseInt(id) === teacherId) : false;
          const hasRejectedInvitation = invitationStatus.rejected_teachers ?
            invitationStatus.rejected_teachers.some(id => parseInt(id) === teacherId) : false;
          const hasExpiredInvitation = invitationStatus.expired_teachers ?
            invitationStatus.expired_teachers.some(id => parseInt(id) === teacherId) : false;
          const isAcceptedTeacher = invitationStatus.accepted_teachers ?
            invitationStatus.accepted_teachers.hasOwnProperty(teacherId.toString()) : false;


          if (hasExpiredInvitation || hasRejectedInvitation) {
            const rejectedSpan = document.createElement('span');
            rejectedSpan.className = 'invitation-rejected';
            rejectedSpan.textContent = 'Recusado';
            nameCell.appendChild(rejectedSpan);
          } else if (hasPendingInvitation) {
            const pendingSpan = document.createElement('span');
            pendingSpan.className = 'invitation-pending';
            pendingSpan.textContent = 'Aguardando resposta';
            nameCell.appendChild(pendingSpan);
          } else if (isAcceptedTeacher) {
            // Only show contract info if user can view it (admin or GESE)
            if (canViewContractInfo()) {
              const contractDiv = document.createElement('div');
              contractDiv.style.display = 'inline-block';
              contractDiv.style.marginLeft = '10px';

              const label = document.createElement('label');
              label.className = 'contract-label';
              label.textContent = 'Contratado:';

              const textarea = document.createElement('textarea');
              textarea.className = 'contract-textarea';
              textarea.placeholder = 'Informações do contrato...';
              textarea.value = invitationStatus.accepted_teachers[teacherId.toString()] || '';

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
            } else {
              // For GEDTH and Pedagogico, just show "Contratado" status
              const acceptedSpan = document.createElement('span');
              acceptedSpan.className = 'invitation-contracted';
              acceptedSpan.textContent = 'Contratado';
              acceptedSpan.style.marginLeft = '10px';
              nameCell.appendChild(acceptedSpan);
            }
          } else if (hasAptoStatus && canSendInvites()) {
            // Show invite button for admin and GEDTH
            const actionButton = document.createElement('button');
            actionButton.className = 'action-button';
            actionButton.textContent = 'Enviar Convite';
            actionButton.onclick = (e) => {
              e.stopPropagation();
              const teacherType = window.location.pathname.includes('docentes-pos') ? 'postgraduate' : 'regular';
              openInvitationModal(
                teacher.id,
                teacher.name,
                teacher.email,
                selectedCourseId,
                selectedCourseName,
                teacherType
              );
            };

            nameCell.appendChild(actionButton);
          }
        }

        row.appendChild(nameCell);

        const emailCell = document.createElement('td');
        emailCell.textContent = teacher.email || '';
        row.appendChild(emailCell);

        const createdCell = document.createElement('td');
        createdCell.textContent = formatDate(teacher.created_at);
        row.appendChild(createdCell);

        const calledCell = document.createElement('td');
        calledCell.className = 'called-at-cell';

        if (isFilteredByCourse && teacher.discipline_statuses) {
          const disciplines = teacher.discipline_statuses.split('|~~|');
          let earliestDate = null;

          disciplines.forEach(disc => {
            const parts = disc.split('|~|');
            if (parts.length >= 5 && parts[4]) { // Changed from >= 4 to >= 5, and parts[3] to parts[4]
              if (!earliestDate || parts[4] < earliestDate) {
                earliestDate = parts[4];
              }
            }
          });

          calledCell.textContent = earliestDate || '';
          calledCell.style.display = '';
        } else {
          calledCell.style.display = 'none';
        }

        row.appendChild(calledCell);

        const disciplinesCell = document.createElement('td');
        if (teacher.discipline_statuses) {
          const disciplineGroups = teacher.discipline_statuses.split('|~~|');
          disciplineGroups.forEach(group => {
            const parts = group.split('|~|');
            
            // The actual data format appears to be:
            // [0] = discipline ID
            // [1] = discipline name  
            // [2] = activity name (Docente, Docente Conteudista, etc.)
            // [3] = called date or status (if numeric/null) or date (if contains /)
            
            if (parts.length >= 3) {
              const disciplineId = parts[0];
              const disciplineName = parts[1];
              let activityName = '';
              let status = 'null'; // Default to Aguardando
              let calledAt = '';
              
              if (parts.length === 4) {
                // Format: id|~|name|~|activityName|~|dateOrStatus
                activityName = parts[2];
                // Check if last part is a date or status
                if (parts[3] && parts[3].includes('/')) {
                  calledAt = parts[3];
                  status = 'null'; // If we have a date but no status, default to Aguardando
                } else {
                  status = parts[3] || 'null';
                }
              } else if (parts.length === 5) {
                // Format: id|~|name|~|activityName|~|status|~|calledAt
                activityName = parts[2];
                status = parts[3] || 'null';
                calledAt = parts[4] || '';
              } else if (parts.length === 3) {
                // Format: id|~|name|~|status
                status = parts[2] || 'null';
              }

              const div = document.createElement('div');
              div.className = 'discipline-status';

              const nameSpan = document.createElement('span');
              nameSpan.className = 'discipline-name';
              nameSpan.textContent = disciplineName;

              // Add activity badge if activity name exists
              if (activityName && activityName.trim() !== '') {
                const activityBadge = document.createElement('span');
                activityBadge.className = 'activity-badge';
                activityBadge.textContent = activityName;
                nameSpan.appendChild(document.createTextNode(' ')); // Add space
                nameSpan.appendChild(activityBadge);
              }

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
      }

      const hasData = currentTeachers.length > 0;
      document.getElementById('export-btn').disabled = !hasData;
      document.getElementById('export-pdf-btn').disabled = !hasActiveFilters();

    } finally {
      isRendering = false;
    }
  }

  // Updated fetchFilteredData function to ensure proper cache clearing
  async function fetchFilteredData() {

    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    // Update isFilteredByCourse flag
    isFilteredByCourse = course !== '';

    // ALWAYS clear invitation statuses when filters change or data is refreshed
    invitationStatuses = {};

    if (!category && !course && !status && !name) {
      currentTeachers = [...allTeachers];
      await updateTable();
      return;
    }

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    try {
      const response = await fetch('../backend/api/get_filtered_teachers.php?' + queryParams);
      const data = await response.json();

      currentTeachers = data;
      await updateTable();
    } catch (error) {
      currentTeachers = [];
      await updateTable();
    }

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
    fetch(`../backend/api/export_docentes_excel.php?${queryParams}`)
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
    fetch(`../backend/api/export_docentes_pdf.php?${queryParams}`)
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
  // Modal functions
  function openInvitationModal(teacherId, teacherName, teacherEmail, courseId, courseName, teacherType) {
    document.getElementById('teacherId').value = teacherId;
    document.getElementById('teacherEmail').value = teacherEmail;
    document.getElementById('courseId').value = courseId;
    document.getElementById('teacherName').textContent = teacherName;
    document.getElementById('courseName').textContent = courseName;
    document.getElementById('teacherType').value = teacherType;

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


        // Clear ALL caches
        invitationStatuses = {};

        // Wait a moment for the database to be updated
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Force fresh data fetch
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

  // After all your functions are defined, at the very end of your script, have just one:
  document.addEventListener('DOMContentLoaded', function() {

    // Disable both export buttons initially
    document.getElementById('export-btn').disabled = true;
    document.getElementById('export-pdf-btn').disabled = true;

    const courseSelect = document.getElementById('course');
    if (courseSelect && courseSelect.value) {
      // Small delay to ensure everything is initialized
      setTimeout(() => {
        invitationStatuses = {};
        fetchFilteredData();
      }, 100);
    } else {
      // Initialize table without filters
      updateTable();
    }
  });
  // Add event listeners with debug
  document.getElementById('category').addEventListener('change', function() {
    fetchFilteredData();
  });
  document.getElementById('course').addEventListener('change', function() {
    fetchFilteredData();
  });
  document.getElementById('status').addEventListener('change', function() {
    fetchFilteredData();
  });
  document.getElementById('name').addEventListener('input', function() {
    fetchFilteredData();
  });
</script>

<?php require_once '../components/footer.php'; ?>