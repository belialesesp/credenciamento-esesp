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
$technicians = get_technicians($conn);

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

  .filter-group select {
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
</style>

<div class="container">
  <a href="home.php" class="btn btn-info mt-5">← Voltar ao Início</a>
  <h1 class="main-title">Técnicos</h1>
  
  <div class="filter-container">
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
    <span class="export-status" id="exportStatus"></span>
  </div>

  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Data de Inscrição</th>
        <th>Data de Chamamento</th>
        <th>Situação</th>
      </tr>
    </thead>
    <tbody id="techniciansTableBody">
  <?php foreach ($technicians as $technician): 
    $enabled = match ($technician['enabled']) {
      1 => 'Apto',
      0 => 'Inapto',
      default => 'Aguardando', 
    };
    $statusClass = match ($technician['enabled']) {
      1 => 'status-approved',
      0 => 'status-not-approved',
      default => 'status-pending',
    };

    $created_at = $technician['created_at'];
    $date = new DateTime($created_at);
    $dateF = $date->format('d/m/Y H:i');
    
    // Format called_at date
    $called_at = $technician['called_at'] ?? null;
    $calledDateF = '';
    if ($called_at) {
      $calledDate = new DateTime($called_at);
      $calledDateF = $calledDate->format('d/m/Y');
    } else {
      $calledDateF = '-';
    }
  ?>
  <tr class="technician-row" onclick="window.location.href='tecnico.php?id=<?= $technician['id']?>'">
    <td><?= titleCase($technician['name']) ?></td>
    <td><?= strtolower($technician['email']) ?></td>
    <td><?= $dateF ?></td>
    <td><?= $calledDateF ?></td>
    <td><span class="<?= $statusClass ?>"><?= $enabled ?></span></td>
  </tr>
  <?php endforeach; ?>
</tbody>
  </table>
</div>

<script>
  // Load page with initial filters if any
  document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for status filter
    document.getElementById('status').addEventListener('change', fetchFilteredData);
  });

  function fetchFilteredData() {
    const status = document.getElementById('status').value;

    console.log('=== Filter Debug ===');
    console.log('Status:', status);

    const queryParams = new URLSearchParams();
    if (status && status !== '') queryParams.append('status', status);

    const url = `../backend/api/get_filtered_technicians.php?${queryParams.toString()}`;
    console.log('Fetching:', url);

    // Show loading state
    const tbody = document.getElementById('techniciansTableBody');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Carregando...</td></tr>';

    fetch(url)
      .then(response => {
        console.log('Response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Received data:', data);
        updateTable(data);
      })
      .catch(error => {
        console.error('Erro:', error);
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Erro ao carregar dados</td></tr>';
      });
  }

  function updateTable(technicians) {
    const tbody = document.getElementById('techniciansTableBody');
    
    if (technicians.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum técnico encontrado</td></tr>';
      return;
    }

    let html = '';
    technicians.forEach(technician => {
      const enabled = technician.enabled == 1 ? 'Apto' : 
                     technician.enabled == 0 ? 'Inapto' : 'Aguardando';
      
      const statusClass = technician.enabled == 1 ? 'status-approved' : 
                         technician.enabled == 0 ? 'status-not-approved' : 'status-pending';

      const date = new Date(technician.created_at);
      const dateF = date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});

      html += `
        <tr class="technician-row" onclick="window.location.href='tecnico.php?id=${technician.id}'">
          <td>${titleCase(technician.name)}</td>
          <td>${technician.email.toLowerCase()}</td>
          <td>${technician.phone}</td>
          <td>${dateF}</td>
          <td><span class="${statusClass}">${enabled}</span></td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
  }

  function titleCase(str) {
    return str.toLowerCase().replace(/\b\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }

  function exportToPDF() {
    const statusElement = document.getElementById('exportStatus');
    const button = document.querySelector('button[onclick="exportToPDF()"]');
    
    // Disable button and show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando PDF...';
    statusElement.textContent = 'Preparando exportação...';
    statusElement.className = '';
    
    // Get current filter values
    const status = document.getElementById('status').value;
    
    // Build URL with current filters
    const queryParams = new URLSearchParams();
    if (status && status !== '') queryParams.append('status', status);
    
    const exportUrl = `../backend/api/export_technicians_pdf.php?${queryParams.toString()}`;
    
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
        link.download = `tecnicos_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        // Show success message
        statusElement.textContent = 'PDF exportado com sucesso!';
        statusElement.className = '';
        
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
        statusElement.className = 'text-danger';
        
        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-file-pdf"></i> Exportar PDF';
      });
  }
</script>