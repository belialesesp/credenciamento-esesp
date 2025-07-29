<?php
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

// Check if user is admin
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
                  if (count($parts) >= 3):
                    $disciplineId = $parts[0];
                    $disciplineName = $parts[1];
                    $status = $parts[2];
                    $statusLabel = parseStatusLabel($status);
                    $statusClass = getStatusClass($status);
                ?>
                    <div class="discipline-status">
                      <span class="discipline-name"><?= htmlspecialchars($disciplineName) ?></span>
                      <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </div>
                <?php
                  endif;
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

<script>
  let allTeachers = <?php echo json_encode($teachers); ?>;
  let currentTeachers = [...allTeachers];
  let isFilteredByCourse = false;
  const isAdmin = <?php echo json_encode($isAdmin); ?>;

  function updateTable() {
    const tbody = document.getElementById('teachers-table-body');
    tbody.innerHTML = '';

    // Show/hide called_at column based on course filter
    const calledAtHeader = document.getElementById('called-at-header');
    const calledAtCells = document.querySelectorAll('.called-at-cell');

    if (isFilteredByCourse) {
      calledAtHeader.style.display = '';
    } else {
      calledAtHeader.style.display = 'none';
    }

    // Sort by called_at when filtered by course
    if (isFilteredByCourse) {
      currentTeachers.sort((a, b) => {
        // Extract called_at from discipline data
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

    currentTeachers.forEach((teacher) => {
      const row = document.createElement('tr');

      // Make row clickable if admin
      if (isAdmin) {
        row.style.cursor = 'pointer';
        row.onclick = () => {
          window.location.href = `docente.php?id=${teacher.id}`;
        };
      }

      // Name cell
      const nameCell = document.createElement('td');
      nameCell.textContent = teacher.name || '';

      // Add button only to the first item when filtered by course
      if (isFilteredByCourse && isFirstInList) {
        const actionButton = document.createElement('button');
        actionButton.className = 'action-button';
        actionButton.textContent = 'Ação';
        actionButton.onclick = (e) => {
          e.stopPropagation(); // Prevent row click
          console.log('Action button clicked for:', teacher.name);
          // Functionality to be added later
        };
        nameCell.appendChild(actionButton);
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
    document.getElementById('export-pdf-btn').disabled = !hasData;
  }

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

  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    // Check if filtering by course
    isFilteredByCourse = course !== '';

    if (!category && !course && !status && !name) {
      currentTeachers = [...allTeachers];
      updateTable();
      return;
    }

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status && status !== 'no-disciplines') queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    fetch('../backend/api/get_filtered_teachers.php?' + queryParams)
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
  // Initialize the table with click handlers
  document.addEventListener('DOMContentLoaded', function() {
    updateTable();
  });
</script>

<?php require_once '../components/footer.php'; ?>