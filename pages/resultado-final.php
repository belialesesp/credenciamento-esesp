<?php
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

// Get all regular courses
$coursesSql = "SELECT id, name FROM disciplinas ORDER BY name";
$coursesStmt = $conn->prepare($coursesSql);
$coursesStmt->execute();
$regularCourses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all post-graduation courses
$postgCoursesSql = "SELECT id, name FROM postg_disciplinas ORDER BY name";
$postgCoursesStmt = $conn->prepare($postgCoursesSql);
$postgCoursesStmt->execute();
$postgCourses = $postgCoursesStmt->fetchAll(PDO::FETCH_ASSOC);

function truncate_text($text, $length = 50, $suffix = '...') {
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
    .results-container {
        margin: 20px 0;
    }
    
    .section-title {
        background-color: #f8f9fa;
        padding: 15px;
        margin: 30px 0 20px 0;
        border-left: 4px solid #007bff;
        font-size: 1.2em;
        font-weight: bold;
    }
    
    .course-section {
        margin-bottom: 40px;
    }
    
    .course-title {
        background-color: #e9ecef;
        padding: 10px 15px;
        margin: 20px 0 10px 0;
        font-weight: bold;
        border-radius: 4px;
    }
    
    .no-teachers {
        padding: 20px;
        text-align: center;
        color: #6c757d;
        font-style: italic;
    }
    
    .teacher-count {
        float: right;
        font-size: 0.9em;
        color: #6c757d;
    }
    
    .export-buttons {
        margin: 20px 0;
        text-align: right;
    }
    
    .btn {
        padding: 10px 20px;
        margin: 0 5px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        transition: background-color 0.3s;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #0056b3;
    }
    
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    
    .btn-success:hover {
        background-color: #218838;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
        font-size: 1.2em;
        color: #6c757d;
    }
    
    @media print {
        .filter-container, .export-buttons {
            display: none;
        }
    }
    
    .table th {
        cursor: pointer;
        user-select: none;
    }
    
    .table th:hover {
        background-color: #e9ecef;
    }
    
    .table th.sort-asc::after {
        content: " ▲";
        font-size: 0.8em;
    }
    
    .table th.sort-desc::after {
        content: " ▼";
        font-size: 0.8em;
    }
</style>

<div class="container">
    <h1 class="main-title">Resultado Final - Docentes Aptos</h1>
    <p class="text-muted">Todos os docentes aptos de todos os cursos</p>
    <p class="text-muted"><small>Ordenado por data de chamada | Clique nos cabeçalhos para reordenar</small></p>
    
    <div class="export-buttons">
        <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
        <button class="btn btn-success" onclick="exportToExcel()">Exportar Excel</button>
    </div>
    
    <!-- Regular Teachers Section -->
    <div class="section-title">
        Docentes - Cursos Regulares
        <span class="teacher-count" id="regular-count">Carregando...</span>
    </div>
    <div id="regular-teachers" class="results-container">
        <div class="loading">Carregando docentes regulares...</div>
    </div>
    
    <!-- Post-graduation Teachers Section -->
    <div class="section-title">
        Docentes - Pós-Graduação
        <span class="teacher-count" id="postg-count">Carregando...</span>
    </div>
    <div id="postg-teachers" class="results-container">
        <div class="loading">Carregando docentes de pós-graduação...</div>
    </div>
</div>

<script>
// Store all teachers data for export
let allTeachersData = {
    regular: [],
    postg: []
};

// Load regular teachers with apto status
function loadRegularTeachers() {
    fetch('../backend/api/get_filtered_teachers.php?status=1')
        .then(response => response.json())
        .then(teachers => {
            allTeachersData.regular = teachers;
            displayRegularTeachers(teachers);
        })
        .catch(error => {
            console.error('Error loading regular teachers:', error);
            document.getElementById('regular-teachers').innerHTML = 
                '<div class="no-teachers">Erro ao carregar docentes regulares</div>';
        });
}

// Load post-graduation teachers with apto status
function loadPostgTeachers() {
    fetch('../backend/api/get_filtered_teachers_postg.php?status=1')
        .then(response => response.json())
        .then(teachers => {
            allTeachersData.postg = teachers;
            displayPostgTeachers(teachers);
        })
        .catch(error => {
            console.error('Error loading postg teachers:', error);
            document.getElementById('postg-teachers').innerHTML = 
                '<div class="no-teachers">Erro ao carregar docentes de pós-graduação</div>';
        });
}

// Display regular teachers grouped by course
function displayRegularTeachers(teachers) {
    const container = document.getElementById('regular-teachers');
    const courses = <?php echo json_encode($regularCourses); ?>;
    
    if (teachers.length === 0) {
        container.innerHTML = '<div class="no-teachers">Nenhum docente apto encontrado</div>';
        document.getElementById('regular-count').textContent = '0 docentes';
        return;
    }
    
    // Group teachers by course
    const teachersByCourse = {};
    const uniqueTeachers = new Set();
    
    teachers.forEach(teacher => {
        if (teacher.discipline_statuses) {
            const statuses = teacher.discipline_statuses.split('|~~|');
            statuses.forEach(statusStr => {
                const parts = statusStr.split('|~|');
                if (parts.length >= 3) {
                    const courseId = parts[0];
                    const courseName = parts[1];
                    const status = parts[2];
                    
                    // Only include if status is '1' (apto)
                    if (status === '1') {
                        if (!teachersByCourse[courseId]) {
                            teachersByCourse[courseId] = {
                                name: courseName,
                                teachers: new Set()
                            };
                        }
                        teachersByCourse[courseId].teachers.add(teacher);
                        uniqueTeachers.add(teacher.id);
                    }
                }
            });
        }
    });
    
    // Build HTML
    let html = '';
    courses.forEach(course => {
        if (teachersByCourse[course.id]) {
            const courseData = teachersByCourse[course.id];
            const teachersArray = Array.from(courseData.teachers);
            
            // Sort teachers by called_at date (most recent first)
            teachersArray.sort((a, b) => {
                const dateA = a.called_at ? new Date(a.called_at) : new Date(0);
                const dateB = b.called_at ? new Date(b.called_at) : new Date(0);
                return dateB - dateA;
            });
            
            html += `<div class="course-section">`;
            html += `<div class="course-title">${course.name} <span class="teacher-count">${teachersArray.length} docentes</span></div>`;
            html += `<table class="table table-striped" data-course-id="${course.id}">`;
            html += `<thead>
                        <tr>
                            <th onclick="sortTable(this, 0, 'text')">Nome</th>
                            <th onclick="sortTable(this, 1, 'date')">Chamado em</th>
                            <th onclick="sortTable(this, 2, 'date')">Inscrição</th>
                        </tr>
                     </thead>`;
            html += `<tbody>`;
            
            teachersArray.forEach(teacher => {
                const createdAt = new Date(teacher.created_at);
                const dateFormatted = createdAt.toLocaleDateString('pt-BR');
                
                const calledAt = teacher.called_at ? new Date(teacher.called_at) : null;
                const calledAtFormatted = calledAt ? calledAt.toLocaleDateString('pt-BR') : '---';
                
                html += `<tr>`;
                html += `<td>${titleCase(teacher.name)}</td>`;
                html += `<td>${calledAtFormatted}</td>`;
                html += `<td>${dateFormatted}</td>`;
                html += `</tr>`;
            });
            
            html += `</tbody></table></div>`;
        }
    });
    
    if (html === '') {
        html = '<div class="no-teachers">Nenhum docente apto encontrado</div>';
    }
    
    container.innerHTML = html;
    document.getElementById('regular-count').textContent = `${uniqueTeachers.size} docentes únicos`;
}

// Display post-graduation teachers grouped by course
function displayPostgTeachers(teachers) {
    const container = document.getElementById('postg-teachers');
    const courses = <?php echo json_encode($postgCourses); ?>;
    
    if (teachers.length === 0) {
        container.innerHTML = '<div class="no-teachers">Nenhum docente apto encontrado</div>';
        document.getElementById('postg-count').textContent = '0 docentes';
        return;
    }
    
    // Group teachers by course
    const teachersByCourse = {};
    const uniqueTeachers = new Set();
    
    teachers.forEach(teacher => {
        if (teacher.discipline_statuses) {
            const statuses = teacher.discipline_statuses.split('||');
            statuses.forEach(statusStr => {
                const parts = statusStr.split(':');
                if (parts.length >= 3) {
                    const courseId = parts[0];
                    const courseName = parts[1];
                    const status = parts[2];
                    
                    // Only include if status is '1' (apto)
                    if (status === '1') {
                        if (!teachersByCourse[courseId]) {
                            teachersByCourse[courseId] = {
                                name: courseName,
                                teachers: new Set()
                            };
                        }
                        teachersByCourse[courseId].teachers.add(teacher);
                        uniqueTeachers.add(teacher.id);
                    }
                }
            });
        }
    });
    
    // Build HTML
    let html = '';
    courses.forEach(course => {
        if (teachersByCourse[course.id]) {
            const courseData = teachersByCourse[course.id];
            const teachersArray = Array.from(courseData.teachers);
            
            // Sort teachers by called_at date (most recent first)
            teachersArray.sort((a, b) => {
                const dateA = a.called_at ? new Date(a.called_at) : new Date(0);
                const dateB = b.called_at ? new Date(b.called_at) : new Date(0);
                return dateB - dateA;
            });
            
            html += `<div class="course-section">`;
            html += `<div class="course-title">${course.name} <span class="teacher-count">${teachersArray.length} docentes</span></div>`;
            html += `<table class="table table-striped" data-course-id="${course.id}">`;
            html += `<thead>
                        <tr>
                            <th onclick="sortTable(this, 0, 'text')">Nome</th>
                            <th onclick="sortTable(this, 1, 'date')">Chamado em</th>
                            <th onclick="sortTable(this, 2, 'date')">Inscrição</th>
                        </tr>
                     </thead>`;
            html += `<tbody>`;
            
            teachersArray.forEach(teacher => {
                const createdAt = new Date(teacher.created_at);
                const dateFormatted = createdAt.toLocaleDateString('pt-BR');
                
                const calledAt = teacher.called_at ? new Date(teacher.called_at) : null;
                const calledAtFormatted = calledAt ? calledAt.toLocaleDateString('pt-BR') : '---';
                
                html += `<tr>`;
                html += `<td>${titleCase(teacher.name)}</td>`;
                html += `<td>${calledAtFormatted}</td>`;
                html += `<td>${dateFormatted}</td>`;
                html += `</tr>`;
            });
            
            html += `</tbody></table></div>`;
        }
    });
    
    if (html === '') {
        html = '<div class="no-teachers">Nenhum docente apto encontrado</div>';
    }
    
    container.innerHTML = html;
    document.getElementById('postg-count').textContent = `${uniqueTeachers.size} docentes únicos`;
}

// Export to Excel function
function exportToExcel() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Tipo,Curso,Nome,Chamado em,Data Inscrição\n";
    
    // Add regular teachers
    const regularCourses = <?php echo json_encode($regularCourses); ?>;
    allTeachersData.regular.forEach(teacher => {
        if (teacher.discipline_statuses) {
            const statuses = teacher.discipline_statuses.split('|~~|');
            // Group by course to avoid duplicate entries
            const coursesSeen = new Set();
            
            statuses.forEach(statusStr => {
                const parts = statusStr.split('|~|');
                if (parts.length >= 3 && parts[2] === '1') {
                    const courseId = parts[0];
                    
                    if (!coursesSeen.has(courseId)) {
                        coursesSeen.add(courseId);
                        const courseName = regularCourses.find(c => c.id == courseId)?.name || 'Curso Desconhecido';
                        const calledAt = teacher.called_at ? new Date(teacher.called_at).toLocaleDateString('pt-BR') : '---';
                        
                        csvContent += `"Regular","${courseName}","${teacher.name}","${calledAt}","${new Date(teacher.created_at).toLocaleDateString('pt-BR')}"\n`;
                    }
                }
            });
        }
    });
    
    // Add post-graduation teachers
    const postgCourses = <?php echo json_encode($postgCourses); ?>;
    allTeachersData.postg.forEach(teacher => {
        if (teacher.discipline_statuses) {
            const statuses = teacher.discipline_statuses.split('||');
            // Group by course to avoid duplicate entries
            const coursesSeen = new Set();
            
            statuses.forEach(statusStr => {
                const parts = statusStr.split(':');
                if (parts.length >= 3 && parts[2] === '1') {
                    const courseId = parts[0];
                    
                    if (!coursesSeen.has(courseId)) {
                        coursesSeen.add(courseId);
                        const courseName = postgCourses.find(c => c.id == courseId)?.name || 'Curso Desconhecido';
                        const calledAt = teacher.called_at ? new Date(teacher.called_at).toLocaleDateString('pt-BR') : '---';
                        
                        csvContent += `"Pós-Graduação","${courseName}","${teacher.name}","${calledAt}","${new Date(teacher.created_at).toLocaleDateString('pt-BR')}"\n`;
                    }
                }
            });
        }
    });
    
    // Create download link
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "docentes_aptos_" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Helper function for title case
function titleCase(str) {
    if (!str) return '';
    return str.toLowerCase().split(' ').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
}

// Table sorting function
function sortTable(header, columnIndex, type) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Determine sort direction
    const isAscending = header.classList.contains('sort-asc');
    
    // Remove sort classes from all headers
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add appropriate sort class
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        let comparison = 0;
        
        if (type === 'date') {
            // Parse Brazilian date format (dd/mm/yyyy)
            const parseDate = (dateStr) => {
                if (dateStr === '---') return new Date(0);
                const parts = dateStr.split('/');
                return new Date(parts[2], parts[1] - 1, parts[0]);
            };
            
            const aDate = parseDate(aValue);
            const bDate = parseDate(bValue);
            comparison = aDate - bDate;
        } else {
            comparison = aValue.localeCompare(bValue, 'pt-BR');
        }
        
        return isAscending ? -comparison : comparison;
    });
    
    // Reorder rows in the table
    rows.forEach(row => tbody.appendChild(row));
}

// Load all data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRegularTeachers();
    loadPostgTeachers();
});
</script>

<?php require_once '../components/footer.php'; ?>