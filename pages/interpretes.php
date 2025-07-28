<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
  header('Location: home.php');
  exit();
}
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/classes/database.class.php';
require_once '../backend/api/get_registers.php';

$conection = new Database();
$conn = $conection->connect();

// Get initial data - will be replaced by AJAX
$interpreters = get_interpreters($conn);

// Function to truncate long text
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
    padding: 10px 20px;
    background-color: #3F8624;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
  }

  .export-button:hover {
    background-color: #336b1d;
  }

  .export-button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
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

    <button class="export-button" onclick="exportToPDF()">
      <i class="fas fa-file-pdf"></i> Exportar PDF
    </button>
    <button class="export-button btn btn-success ml-2" onclick="exportToExcel()">
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

<script>
  // Global variables for sorting
  let allInterpreters = <?= json_encode($interpreters) ?>;
  let currentInterpreters = [...allInterpreters];
  let currentSort = {
    column: 'called',
    direction: 'desc'
  };

  // Load initial data
  document.addEventListener('DOMContentLoaded', function() {
    // Set up sort click handlers
    document.querySelectorAll('.sortable').forEach(th => {
      th.addEventListener('click', () => {
        const sortColumn = th.getAttribute('data-sort');
        handleSort(sortColumn);
      });
    });
    fetchFilteredData(); // Load data on page load
    // Set up filter handler
    document.getElementById('status').addEventListener('change', filterInterpreters);

    // Initial sort and render with called_at as default
    sortInterpreters();
    renderTable(currentInterpreters);
    updateStats();

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

      } else if (currentSort.column === 'created') {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        compareResult = dateA - dateB;

      } else if (currentSort.column === 'called') {
        // Handle null values - always put them at the end
        const dateA = a.called_at ? new Date(a.called_at) : null;
        const dateB = b.called_at ? new Date(b.called_at) : null;

        if (dateA === null && dateB === null) {
          compareResult = 0;
        } else if (dateA === null) {
          return 1; // Always put nulls at the end
        } else if (dateB === null) {
          return -1; // Always put nulls at the end
        } else {
          // Both have dates - for desc order, newer dates should come first
          compareResult = currentSort.direction === 'desc' ? dateB - dateA : dateA - dateB;
        }
      }

      // Apply sort direction only if not already handled
      if (currentSort.column !== 'called') {
        return currentSort.direction === 'asc' ? compareResult : -compareResult;
      }

      return compareResult;
    });
  }

  function fetchFilteredData() {
    const status = document.getElementById('status').value;
    const name = document.getElementById('name').value;

    const queryParams = new URLSearchParams();
    if (status) queryParams.append('status', status);
    if (name) queryParams.append('name', name);

    fetch('../backend/api/get_filtered_interpreters.php?' + queryParams.toString())
      .then(response => response.json())
      .then(data => {
        // Store the data
        allInterpreters = data;
        currentInterpreters = [...data];

        // Apply any additional filters if needed
        filterInterpreters();
      })
      .catch(error => console.error('Error:', error));
  }

  // Also add the filterInterpreters function if it doesn't exist
  function filterInterpreters() {
    const statusFilter = document.getElementById('status').value;

    if (!statusFilter) {
      currentInterpreters = [...allInterpreters];
    } else if (statusFilter === 'null') {
      currentInterpreters = allInterpreters.filter(i =>
        i.enabled === null || i.enabled === ''
      );
    } else {
      currentInterpreters = allInterpreters.filter(i =>
        String(i.enabled) === statusFilter
      );
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

  function renderTable(interpreters) {
    const tbody = document.getElementById('interpretersTableBody');
    tbody.innerHTML = '';

    if (interpreters.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum intérprete encontrado</td></tr>';
      return;
    }

    interpreters.forEach(interpreter => {
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

      // Format called_at date
      let calledDateF = '-';
      if (interpreter.called_at) {
        const calledDate = new Date(interpreter.called_at);
        calledDateF = calledDate.toLocaleDateString('pt-BR');
      }

      // Create row element
      const row = document.createElement('tr');
      row.className = 'interpreter-row';
      row.style.cursor = 'pointer';
      row.onclick = () => {
        window.location.href = `interprete.php?id=${interpreter.id}`;
      };

      row.innerHTML = `
      <td>${titleCase(interpreter.name)}</td>
      <td>${interpreter.email.toLowerCase()}</td>
      <td>${createdDateF}</td>
      <td>${calledDateF}</td>
      <td><span class="${statusClass}">${enabled}</span></td>
    `;

      tbody.appendChild(row);
    });
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

  // Title case function
  function titleCase(str) {
    if (!str) return '';
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(match) {
      return match.toUpperCase();
    });
  }
function exportToExcel() {
  const button = event.target.closest('button');
  const originalText = button.innerHTML;
  const statusElement = document.getElementById('exportStatus');
  
  // Disable button
  button.disabled = true;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
  
  // Clear previous status
  statusElement.textContent = '';
  statusElement.className = 'export-status';
  
  // Get current filter
  const statusFilter = document.getElementById('status').value;
  const queryParams = new URLSearchParams();
  if (statusFilter) queryParams.append('status', statusFilter);
  

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
      a.download = `interpretes_${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      
      // Show success message
      statusElement.textContent = 'Excel exportado com sucesso!';
      statusElement.className = 'export-status text-success';
      
      // Reset button
      button.disabled = false;
      button.innerHTML = originalText;
      
      // Clear success message after 3 seconds
      setTimeout(() => {
        statusElement.textContent = '';
      }, 3000);
    })
    .catch(error => {
      console.error('Erro na exportação:', error);
      statusElement.textContent = 'Erro ao exportar Excel. Tente novamente.';
      statusElement.className = 'export-status text-danger';
      
      // Reset button
      button.disabled = false;
      button.innerHTML = originalText;
    });
}
  // Export to PDF
  function exportToPDF() {
    const button = document.querySelector('.export-button');
    const statusElement = document.getElementById('exportStatus');

    // Disable button
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';

    // Clear previous status
    statusElement.textContent = '';
    statusElement.className = 'export-status';

    // Get current filter
    const statusFilter = document.getElementById('status').value;
    const queryParams = new URLSearchParams();
    if (statusFilter) queryParams.append('status', statusFilter);

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
        a.download = `interpretes_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        // Show success message
        statusElement.textContent = 'PDF exportado com sucesso!';
        statusElement.className = 'export-status text-success';

        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-file-pdf"></i> Exportar PDF';

        // Clear success message after 3 seconds
        setTimeout(() => {
          statusElement.textContent = '';
        }, 3000);
      })
      .catch(error => {
        console.error('Erro na exportação:', error);
        statusElement.textContent = 'Erro ao exportar PDF. Tente novamente.';
        statusElement.className = 'export-status text-danger';

        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-file-pdf"></i> Exportar PDF';
      });
  }
</script>

<?php include '../components/footer.php'; ?>