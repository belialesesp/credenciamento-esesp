<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
  header('Location: home.php');
  exit();
}
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

// Don't load teachers here - let JavaScript handle it
$teachers = [];
$courses = get_all_courses($conn);

function truncate_text($text, $length = 50, $suffix = '...')
{
  $text = trim(preg_replace('/\s+/', ' ', $text));
  if (strlen($text) <= $length) {
    return $text;
  }
  $truncated = substr($text, 0, $length);
  $lastSpace = strrpos($truncated, ' ');
  if ($lastSpace !== false) {
    $truncated = substr($truncated, 0, $lastSpace);
  }
  return $truncated . $suffix;
}
?>

<style>
  .discipline-status {
    display: inline-block;
    padding: 2px 8px;
    margin: 2px;
    border-radius: 4px;
    font-size: 12px;
    background-color: #f0f0f0;
  }

  .discipline-status.status-approved {
    background-color: #d4edda;
    color: #155724;
  }

  .discipline-status.status-not-approved {
    background-color: #f8d7da;
    color: #721c24;
  }

  .discipline-status.status-pending {
    background-color: #fff3cd;
    color: #856404;
  }

  .discipline-info {
    margin-bottom: 8px;
  }

  .teacher-row {
    cursor: pointer;
  }

  .teacher-row:hover {
    background-color: #f5f5f5;
  }

  .action-buttons {
    display: flex;
    align-items: center;
    margin: 20px 0;
  }

  .btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: background-color 0.3s;
  }

  .btn-success {
    background-color: #28a745;
    color: white;
  }

  .btn-success:hover {
    background-color: #218838;
  }

  .btn:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
  }

  #export-status {
    color: #28a745;
    font-size: 14px;
  }

  .export-error {
    color: #dc3545 !important;
  }

  .disciplines-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .discipline-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    border-bottom: 1px solid #eee;
  }

  .discipline-item:last-child {
    border-bottom: none;
  }

  .discipline-name {
    flex: 1;
    font-weight: 500;
  }

  .discipline-item small {
    font-size: 0.85em;
    margin-left: 8px;
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
</style>

<div class="container">
  <a href="home.php" class="btn btn-info mt-5">← Voltar ao Início</a>
  <h1 class="main-title">Docentes</h1>
  <div class="filter-container">
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
  <div class="action-buttons">
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
    <span id="export-status" style="margin-left: 10px; font-weight: bold;"></span>
</div>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th class="sortable" data-sort="name">
          Nome
          <span class="sort-indicator" id="sort-name"></span>
        </th>
        <th>Email</th>
        <th class="sortable" data-sort="date">
          Data de Inscrição
          <span class="sort-indicator active" id="sort-date">↑</span>
        </th>
        <th>Cursos e Status</th>
      </tr>
    </thead>
    <tbody>
      <!-- Table will be populated by JavaScript -->
    </tbody>
  </table>
</div>

<script>
  // Global variables for sorting
  let currentTeachers = [];
  let currentSort = {
    column: 'date',
    direction: 'asc'
  };

  // Load page with initial state
  document.addEventListener('DOMContentLoaded', function() {
    // Set up sort click handlers
    document.querySelectorAll('.sortable').forEach(th => {
      th.addEventListener('click', () => {
        const sortColumn = th.getAttribute('data-sort');
        handleSort(sortColumn);
      });
    });

    updateExportButtonState();
    fetchFilteredData(); // Load data on page load
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
    sortTeachers();
    renderTable(currentTeachers);
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

  function sortTeachers() {
    currentTeachers.sort((a, b) => {
      let compareResult = 0;

      if (currentSort.column === 'name') {
        // Normalize names: trim whitespace and handle case properly
        const nameA = (a.name || '').trim();
        const nameB = (b.name || '').trim();
        
        // Use localeCompare with proper options for Portuguese sorting
        compareResult = nameA.localeCompare(nameB, 'pt-BR', {
          numeric: true,
          sensitivity: 'accent' // Considers accents but ignores case
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

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);

    const url = `../backend/api/get_filtered_teachers.php?${queryParams.toString()}`;

    // Show loading state
    const tbody = document.querySelector('table tbody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>';

    fetch(url)
      .then(response => response.json())
      .then(data => {
        currentTeachers = data;
        sortTeachers();
        renderTable(currentTeachers);
        updateExportButton(data.length);
        updateExportButtonState();
      })
      .catch(error => {
        console.error('Erro:', error);
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Erro ao carregar dados</td></tr>';
      });
  }

  function renderTable(teachers) {
    const tbody = document.querySelector('table tbody');
    tbody.innerHTML = '';

    if (teachers.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Nenhum docente encontrado</td></tr>';
      return;
    }

    teachers.forEach(teacher => {
      const date = new Date(teacher.created_at);
      const dateF = date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });

      // Parse discipline statuses with NEW delimiters
      let disciplineHtml = '';
      if (teacher.discipline_statuses) {
        disciplineHtml = '<div class="disciplines-list">';
        const statusPairs = teacher.discipline_statuses.split('|~~|');
        statusPairs.forEach(pair => {
          if (pair && pair.trim()) {
            const parts = pair.split('|~|');
            if (parts.length >= 3) {
              const discId = parts[0];
              const discName = parts[1];
              const status = parts[2];
              const discCalledAt = parts[3] || '';

              const statusText = getStatusText(status);
              const statusClass = getStatusClass(status);

              disciplineHtml += `
                <div class="discipline-info">
                  <strong>${escapeHtml(discName)}:</strong>
                  <span class="discipline-status ${statusClass}">${statusText}</span>`;
              
              // Add called_at date if status is Apto and date exists
              if (status === '1' && discCalledAt && discCalledAt !== '0000-00-00 00:00:00') {
                try {
                  const calledDate = new Date(discCalledAt);
                  if (!isNaN(calledDate.getTime())) {
                    const formattedDate = calledDate.toLocaleDateString('pt-BR', {
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric'
                    });
                    disciplineHtml += `<small style="margin-left: 10px; color: #666;">Chamado em: ${formattedDate}</small>`;
                  }
                } catch (e) {
                  console.error('Error parsing date:', discCalledAt, e);
                }
              }
              
              disciplineHtml += `</div>`;
            }
          }
        });
        disciplineHtml += '</div>';
      }

      if (!disciplineHtml || disciplineHtml === '<div class="disciplines-list"></div>') {
        disciplineHtml = '<em>Sem disciplinas</em>';
      }

      const row = `
        <tr class="teacher-row" onclick="window.location.href='docente.php?id=${teacher.id}'">
          <td>${titleCase(teacher.name)}</td>
          <td>${teacher.email.toLowerCase()}</td>
          <td>${dateF}</td>
          <td>${disciplineHtml}</td>
        </tr>
      `;
      tbody.innerHTML += row;
    });
  }

  // Keep existing helper functions unchanged
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function getStatusText(status) {
    const statusStr = String(status).trim();
    switch (statusStr) {
      case '1':
        return 'Apto';
      case '0':
        return 'Inapto';
      case 'null':
      case '':
        return 'Aguardando';
      default:
        return 'Aguardando';
    }
  }

  function getStatusClass(status) {
    const statusStr = String(status).trim();
    switch (statusStr) {
      case '1':
        return 'status-approved';
      case '0':
        return 'status-not-approved';
      case 'null':
      case '':
        return 'status-pending';
      default:
        return 'status-pending';
    }
  }

  function titleCase(str) {
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }

  // Event listeners for filters
  document.getElementById('category').addEventListener('change', fetchFilteredData);
  document.getElementById('course').addEventListener('change', fetchFilteredData);
  document.getElementById('status').addEventListener('change', fetchFilteredData);

  // Export functions
  function updateExportButton(count) {
    const exportBtn = document.getElementById('export-btn');
    exportBtn.textContent = count > 0 ? 
      `Exportar para Excel (${count} ${count === 1 ? 'docente' : 'docentes'})` : 
      'Exportar para Excel';
  }

  function updateExportButtonState() {
    const exportBtn = document.getElementById('export-btn');
    const exportPdfBtn = document.getElementById('export-pdf-btn');
    const hasFilters = document.getElementById('category').value || 
                      document.getElementById('course').value || 
                      document.getElementById('status').value;
    exportBtn.disabled = !hasFilters;
    exportPdfBtn.disabled = !hasFilters;
}

  function exportToExcel() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    if (!category && !course && !status) {
        alert('Por favor, selecione pelo menos um filtro antes de exportar.');
        return;
    }

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);

    const url = `../backend/api/export_docentes_excel.php?${queryParams.toString()}`;
    const exportStatus = document.getElementById('export-status');

    exportStatus.textContent = 'Gerando arquivo...';
    exportStatus.classList.remove('export-error');

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao gerar arquivo');
            }
            return response.blob();
        })
        .then(blob => {
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            // Changed to .csv extension
            a.download = `docentes_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);

            exportStatus.textContent = 'Download concluído!';
            setTimeout(() => {
                exportStatus.textContent = '';
            }, 3000);
        })
        .catch(error => {
            console.error('Erro:', error);
            exportStatus.textContent = 'Erro ao gerar arquivo';
            exportStatus.classList.add('export-error');
        });
}
function exportToPDF() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    if (!category && !course && !status) {
        alert('Por favor, selecione pelo menos um filtro antes de exportar.');
        return;
    }

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);

    const url = `../backend/api/export_docentes_pdf.php?${queryParams.toString()}`;
    const exportStatus = document.getElementById('export-status');
    const exportPdfBtn = document.getElementById('export-pdf-btn');

    // Disable button and show loading
    exportPdfBtn.disabled = true;
    exportPdfBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
    
    exportStatus.textContent = 'Gerando PDF...';
    exportStatus.classList.remove('export-error');

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao gerar PDF');
            }
            return response.blob();
        })
        .then(blob => {
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `docentes_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(downloadUrl);
            document.body.removeChild(a);

            exportStatus.textContent = 'PDF exportado com sucesso!';
            exportStatus.className = 'text-success';
            
            // Reset button
            exportPdfBtn.disabled = false;
            exportPdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i> Exportar para PDF';
            
            setTimeout(() => {
                exportStatus.textContent = '';
            }, 3000);
        })
        .catch(error => {
            console.error('Erro:', error);
            exportStatus.textContent = 'Erro ao gerar PDF';
            exportStatus.classList.add('export-error');
            
            // Reset button
            exportPdfBtn.disabled = false;
            exportPdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i> Exportar para PDF';
        });
}

</script>

<?php include '../components/footer.php'; ?>