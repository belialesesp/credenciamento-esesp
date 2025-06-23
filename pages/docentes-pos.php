<?php

require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

// CHANGED: Always get teachers from the filtering API, not from get_registers.php
// This ensures consistent discipline status handling
$teachers = []; // Will be populated by JavaScript, just like regular docentes.php

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
</style>

<div class="container">
  <h1 class="main-title">Docentes / Assessoramento Técnico <br> Pós Graduação</h1>
  <div class="filter-container">
    <div class="filter-group">
      <label for="category">Filtrar por categoria</label>
      <select name="category" id="category">
        <option value=""></option>
        <option value="1">Docente</option>
        <option value="2">Docente Conteudista</option>
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
        <option value="">Todos</option>
        <option value="1">Apto</option>
        <option value="0">Inapto</option>
        <option value="null">Aguardando</option>
        <option value="no-disciplines">Sem disciplinas</option>
      </select>
    </div>
  </div>
  <div class="action-buttons" style="margin: 20px 0;">
    <button type="button" class="btn btn-success" onclick="exportToPDFPos()">
      <i class="fas fa-file-pdf"></i> Exportar para PDF
    </button>
    <span id="export-status-pos" style="margin-left: 10px; font-weight: bold;"></span>
  </div>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Chamado em</th>
        <th>Data de Inscrição</th>
        <th>Cursos e Status</th>
      </tr>
    </thead>
    <tbody>
      <!-- Table will be populated by JavaScript -->
      <tr>
        <td colspan="5" style="text-align: center;">Carregando...</td>
      </tr>
    </tbody>
  </table>
</div>


<script>
  // Load all teachers on page load (no filters applied)
  document.addEventListener('DOMContentLoaded', function() {
    fetchFilteredData(); // Load all teachers initially
    updateExportButtonState(); // Set initial export button state
  });

  // Fetch filtered data based on selected filters
  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    // Debug logging
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
    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Carregando...</td></tr>';

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
        // FIX: Change updateTablePos to updateTable
        updateTable(data);
        
        // Update export button state
        updateExportButtonPos(data.length);
        updateExportButtonState(); // Update based on filters
      })
      .catch(error => {
        console.error('Erro:', error);
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Erro ao carregar dados</td></tr>';
      });
  }

  function exportToPDFPos() {
    const statusElement = document.getElementById('export-status-pos');
    const button = document.querySelector('button[onclick="exportToPDFPos()"]');
    
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
    
    const exportUrl = `../backend/api/export_docentes_pos_pdf.php?${queryParams.toString()}`;
    
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

  function updateExportButtonPos(dataCount) {
    const button = document.querySelector('button[onclick="exportToPDFPos()"]');
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
    
    const button = document.querySelector('button[onclick="exportToPDFPos()"]');
    if (button && !hasFilters) {
      button.disabled = true;
      button.title = 'Aplique filtros para exportar';
      button.style.opacity = '0.6';
    } else if (button && hasFilters) {
      button.style.opacity = '1';
      // Button will be enabled/disabled by updateExportButtonPos based on data count
    }
  }

  // ADD event listeners at the end of the script section
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

  // Update the table with filtered data
  function updateTable(teachers) {
    const tbody = document.querySelector('table tbody');
    tbody.innerHTML = '';

    if (teachers.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum docente encontrado</td></tr>';
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

      const calledAt = teacher.called_at && teacher.called_at !== '0000-00-00 00:00:00'
        ? new Date(teacher.called_at).toLocaleDateString('pt-BR')
        : '---';

      // Parse discipline statuses
      let disciplineStatuses = [];
      if (teacher.discipline_statuses) {
        const statusPairs = teacher.discipline_statuses.split('||');
        statusPairs.forEach(pair => {
          if (pair) {
            const parts = pair.split(':');
            if (parts.length >= 3) {
              disciplineStatuses.push({
                id: parts[0],
                name: parts.slice(1, -1).join(':'), // Handle names with colons
                status: parts[parts.length - 1] === 'null' ? null : parseInt(parts[parts.length - 1])
              });
            }
          }
        });
      }

      // Build discipline HTML
      let disciplineHtml = '';
      if (disciplineStatuses.length === 0) {
        disciplineHtml = '<span class="text-muted">Sem disciplinas</span>';
      } else {
        disciplineStatuses.forEach(disc => {
          const statusText = disc.status === 1 ? 'Apto' :
            disc.status === 0 ? 'Inapto' :
            'Aguardando';
          const statusClass = disc.status === 1 ? 'status-approved' :
            disc.status === 0 ? 'status-not-approved' :
            'status-pending';

          disciplineHtml += `
            <div class="discipline-info">
              <strong>${escapeHtml(disc.name)}:</strong>
              <span class="discipline-status ${statusClass}">${statusText}</span>
            </div>
          `;
        });
      }

      const row = `
        <tr class="teacher-row" onclick="window.location.href='docente-pos.php?id=${teacher.id}'">
          <td>${titleCase(teacher.name)}</td>
          <td>${teacher.email.toLowerCase()}</td>
          <td>${calledAt}</td>
          <td>${dateF}</td>
          <td>${disciplineHtml}</td>
        </tr>
      `;
      tbody.innerHTML += row;
    });
  }

  // Utility functions
  function titleCase(str) {
    return str.toLowerCase().split(' ').map(word => 
      word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
  }

  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }
</script>

<?php include '../components/footer.php'; ?>