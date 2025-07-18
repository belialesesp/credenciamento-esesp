<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: home.php');
    exit();
}
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

// Don't load teachers here - let JavaScript handle it
$teachers = [];
$courses = get_all_postg_courses($conn);

function truncate_text($text, $length = 50, $suffix = '...')
{
  // Remove extra whitespace and line breaks
  $text = trim(preg_replace('/\s+/', ' ', $text));

  // If text is already short enough, return it
  if (strlen($text) <= $length) {
    return $text;
  }

  // Find the last space within the length limit
  $truncated = substr($text, 0, $length);
  $lastSpace = strrpos($truncated, ' ');

  // If we found a space, truncate there; otherwise at the length limit
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
</style>

<div class="container">
  <a href="home.php" class="btn btn-info mt-5">← Voltar ao Início</a>
  <h1 class="main-title">Docentes - Pós-Graduação</h1>
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
        <option value="">Todos</option>
        <option value="1">Apto</option>
        <option value="0">Inapto</option>
        <option value="null">Aguardando</option>
      </select>
    </div>
  </div>
  <div class="action-buttons" style="margin: 20px 0;">
    <button type="button" class="btn btn-success" onclick="exportToPDF()">
      <i class="fas fa-file-pdf"></i> Exportar para PDF
    </button>
    <span id="export-status" style="margin-left: 10px; font-weight: bold;"></span>
  </div>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Data de Inscrição</th>
        <th>Cursos e Status</th>
      </tr>
    </thead>
    <tbody>
      <!-- Table will be populated by JavaScript -->
    </tbody>
  </table>
</div>

<script>
  // Load page with initial state
  document.addEventListener('DOMContentLoaded', function() {
    updateExportButtonState();
    fetchFilteredData(); // Load data on page load
  });

  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    console.log('=== Filter Debug ===');
    console.log('Category:', category);
    console.log('Course:', course);
    console.log('Status:', status);

    const queryParams = new URLSearchParams();
    if (category && category !== '') queryParams.append('category', category);
    if (course && course !== '') queryParams.append('course', course);
    if (status && status !== '') queryParams.append('status', status);

    const url = `../backend/api/get_filtered_teachers_postg.php?${queryParams.toString()}`;
    console.log('Fetching:', url);

    // Show loading state
    const tbody = document.querySelector('table tbody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>';

    fetch(url)
      .then(response => {
        console.log('Response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Received data:', data);
        console.log('Number of teachers:', data.length);
        if (data.length > 0) {
          console.log('First teacher:', data[0]);
        }
        updateTable(data);

        // Update export button state
        updateExportButton(data.length);
        updateExportButtonState(); // Update based on filters
      })
      .catch(error => {
        console.error('Erro:', error);
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Erro ao carregar dados</td></tr>';
      });
  }

  // Replace the updateTable function in docentes-pos.php with this fixed version

function updateTable(teachers) {
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

    // Parse discipline statuses with NEW delimiters (same as docentes.php)
    let disciplineHtml = '';
    if (teacher.discipline_statuses) {
      disciplineHtml = '<div class="disciplines-list">';
      const statusPairs = teacher.discipline_statuses.split('|~~|'); // Changed from '||'
      statusPairs.forEach(pair => {
        if (pair && pair.trim()) {
          const parts = pair.split('|~|'); // Changed from ':'
          if (parts.length >= 3) {
            const discId = parts[0];
            const discName = parts[1];
            const status = parts[2];
            const discCalledAt = parts[3] || ''; // Get called_at date

            const statusText = getStatusText(status);
            const statusClass = getStatusClass(status);

            disciplineHtml += `
              <div class="discipline-info">
                <strong>${escapeHtml(discName)}:</strong>
                <span class="discipline-status ${statusClass}">${statusText}</span>`;
            
            // Add called_at date if status is Apto and date exists
            if (status === '1' && discCalledAt) {
              disciplineHtml += ` <small>Chamado em: ${discCalledAt}</small>`;
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
      <tr class="teacher-row" onclick="window.location.href='docente-pos.php?id=${teacher.id}'">
        <td>${titleCase(teacher.name)}</td>
        <td>${teacher.email.toLowerCase()}</td>
        <td>${dateF}</td>
        <td>${disciplineHtml}</td>
      </tr>
    `;
    tbody.innerHTML += row;
  });
}

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Fixed status functions
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
      console.warn('Unknown status:', status);
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

  // Helper function to escape HTML
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Fixed status functions
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
        console.warn('Unknown status:', status);
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

  function exportToPDF() {
    const statusElement = document.getElementById('export-status');
    const button = document.querySelector('button[onclick="exportToPDF()"]');

    // Disable button and show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando PDF...';
    statusElement.textContent = 'Preparando exportação...';
    statusElement.className = '';

    // Get current filter values
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    // Build URL with current filters
    const queryParams = new URLSearchParams();
    if (category && category !== '') queryParams.append('category', category);
    if (course && course !== '') queryParams.append('course', course);
    if (status && status !== '') queryParams.append('status', status);

    const exportUrl = `../backend/api/export_docentes_postg_pdf.php?${queryParams.toString()}`;

    // Handle the download
    fetch(exportUrl)
      .then(response => {
        if (!response.ok) {
          throw new Error('Erro na exportação');
        }
        return response.blob();
      })
      .then(blob => {
        // Create blob URL and trigger download
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `docentes_pos_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        // Show success message
        statusElement.textContent = 'PDF exportado com sucesso!';
        statusElement.className = '';

        // Clear success message after 3 seconds
        setTimeout(() => {
          statusElement.textContent = '';
        }, 3000);
      })
      .catch(error => {
        console.error('Erro na exportação:', error);
        statusElement.textContent = 'Erro ao exportar PDF. Tente novamente.';
        statusElement.className = 'export-error';

        // Clear error message after 5 seconds
        setTimeout(() => {
          statusElement.textContent = '';
          statusElement.className = '';
        }, 5000);
      })
      .finally(() => {
        // Re-enable button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-file-pdf"></i> Exportar para PDF';
      });
  }

  function updateExportButton(dataCount) {
    const button = document.querySelector('button[onclick="exportToPDF()"]');
    if (button) {
      if (dataCount === 0) {
        button.disabled = true;
        button.title = 'Nenhum dado para exportar';
      } else {
        button.disabled = false;
        button.title = `Exportar ${dataCount} registro(s) para PDF`;
      }
    }
  }

  // NEW: Function to disable export when no filters are applied
  function updateExportButtonState() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    const hasFilters = (category && category !== '') ||
      (course && course !== '') ||
      (status && status !== '');

    const button = document.querySelector('button[onclick="exportToPDF()"]');
    if (button && !hasFilters) {
      button.disabled = true;
      button.title = 'Aplique filtros para exportar';
      button.style.opacity = '0.6';
    } else if (button && hasFilters) {
      button.style.opacity = '1';
      // Button will be enabled/disabled by updateExportButton based on data count
    }
  }

  // ADD event listeners
  document.getElementById('category').addEventListener('change', function() {
    fetchFilteredData();
    updateExportButtonState();
  });

  document.getElementById('course').addEventListener('change', function() {
    fetchFilteredData();
    updateExportButtonState();
  });

  document.getElementById('status').addEventListener('change', function() {
    fetchFilteredData();
    updateExportButtonState();
  });
</script>