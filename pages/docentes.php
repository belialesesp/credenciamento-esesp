<?php

require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

$teachers = get_docente($conn);
$courses = get_all_courses($conn);

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
        <option value="">Todos</option>
        <option value="1">Apto</option>
        <option value="0">Inapto</option>
        <option value="null">Aguardando</option>
        <option value="no-disciplines">Sem disciplinas</option>
      </select>
    </div>
  </div>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Chamado Em</th>
        <th>Data de Inscrição</th>
        <th>Cursos e Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teachers as $teacher):
        $created_at = $teacher['created_at'];
        $date = new DateTime($created_at);
        $dateF = $date->format('d/m/Y H:i');

        // Parse discipline statuses
        $disciplineStatuses = [];
        if (!empty($teacher['discipline_statuses'])) {
          // Use the new delimiter |~~| for records
          $statusPairs = explode('|~~|', $teacher['discipline_statuses']);
          foreach ($statusPairs as $pair) {
            if (!empty($pair)) {
              // Use the new delimiter |~| for fields
              $parts = explode('|~|', $pair);
              if (count($parts) >= 3) {
                list($discId, $discName, $status) = $parts;
                $disciplineStatuses[] = [
                  'id' => $discId,
                  'name' => $discName, // Now contains the full name with colons preserved
                  'status' => $status === 'null' ? null : (int)$status
                ];
              }
            }
          }
        }
      ?>
        <tr class="teacher-row" onclick="window.location.href='docente.php?id=<?= $teacher['id'] ?>'">
          <td><?= titleCase($teacher['name']) ?></td>
          <td><?= strtolower($teacher['email']) ?></td>
          <td><?= isset($teacher['called_at']) && $teacher['called_at'] ? (new DateTime($teacher['called_at']))->format('d/m/Y') : '---' ?></td>
          <td><?= $dateF ?></td>
          <td>
            <?php foreach ($disciplineStatuses as $disc):
              $statusText = match ($disc['status']) {
                1 => 'Apto',
                0 => 'Inapto',
                default => 'Aguardando',
              };
              $statusClass = match ($disc['status']) {
                1 => 'status-approved',
                0 => 'status-not-approved',
                default => 'status-pending',
              };
            ?>
              <div class="discipline-info">
                <strong><?= htmlspecialchars($disc['name']) ?>:</strong>
                <span class="discipline-status <?= $statusClass ?>"><?= $statusText ?></span>
              </div>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  // Add this to your docentes.php fetchFilteredData function
  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    console.log('=== Filter Debug ===');
    console.log('Category:', category);
    console.log('Course:', course);
    console.log('Status:', status);

    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);

    const url = `../backend/api/get_filtered_teachers.php?${queryParams.toString()}`;
    console.log('Fetching:', url);

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
      })
      .catch(error => {
        console.error('Erro:', error);
      });
  }

  // Replace the updateTable function in docentes.php with this:

  function updateTable(teachers) {
    const tbody = document.querySelector('table tbody');
    tbody.innerHTML = '';

    teachers.forEach(teacher => {
      const date = new Date(teacher.created_at);
      const dateF = date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });

      const calledAt = teacher.called_at ?
        new Date(teacher.called_at).toLocaleDateString('pt-BR') :
        '---';

      // Parse discipline statuses with NEW delimiters
      let disciplineHtml = '';
      if (teacher.discipline_statuses) {
        const statusPairs = teacher.discipline_statuses.split('|~~|'); // NEW
        statusPairs.forEach(pair => {
          if (pair && pair.trim()) {
            const parts = pair.split('|~|'); // NEW
            if (parts.length >= 3) {
              const discId = parts[0];
              const discName = parts[1];
              const status = parts[2];

              const statusText = getStatusText(status);
              const statusClass = getStatusClass(status);

              disciplineHtml += `
              <div class="discipline-info">
                <strong>${escapeHtml(discName)}:</strong>
                <span class="discipline-status ${statusClass}">${statusText}</span>
              </div>
            `;
            }
          }
        });
      }

      if (!disciplineHtml) {
        disciplineHtml = '<em>Sem disciplinas</em>';
      }

      const row = `
      <tr class="teacher-row" onclick="window.location.href='docente.php?id=${teacher.id}'">
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

  // Update the status text/class functions to handle all cases
  function getStatusText(status) {
    // Convert to string for consistent comparison
    const statusStr = String(status);

    if (statusStr === 'null' || statusStr === '' || status === null) {
      return 'Aguardando';
    } else if (statusStr === '1') {
      return 'Apto';
    } else if (statusStr === '0') {
      return 'Inapto';
    }
    return 'Aguardando';
  }

  function getStatusClass(status) {
    // Convert to string for consistent comparison
    const statusStr = String(status);

    if (statusStr === 'null' || statusStr === '' || status === null) {
      return 'status-pending';
    } else if (statusStr === '1') {
      return 'status-approved';
    } else if (statusStr === '0') {
      return 'status-not-approved';
    }
    return 'status-pending';
  }

  function titleCase(str) {
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }

  document.getElementById('category').addEventListener('change', fetchFilteredData);
  document.getElementById('course').addEventListener('change', fetchFilteredData);
  document.getElementById('status').addEventListener('change', fetchFilteredData);
</script>