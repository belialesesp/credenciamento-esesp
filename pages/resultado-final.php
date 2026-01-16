<?php
require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/classes/database.class.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<?php
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

        .filter-container,
        .export-buttons {
            display: none;
        }
    }

    .table th {
        cursor: pointer;
        user-select: none;
        text-align: left;
        padding: 12px 8px;
        background-color: #5bc0de;
        color: white;
        font-weight: 600;
    }

    .table th:hover {
        background-color: #46b8da;
    }

    .table th.sort-asc::after {
        content: " ▲";
        font-size: 0.8em;
    }

    .table th.sort-desc::after {
        content: " ▼";
        font-size: 0.8em;
    }

    .table td {
        padding: 12px 8px;
        vertical-align: middle;
    }

    .table {
        width: 100%;
        table-layout: fixed;
    }

    /* Specific column widths for consistent alignment */
    .table th:first-child,
    .table td:first-child {
        width: 50%;
    }

    .table th:nth-child(2),
    .table td:nth-child(2) {
        width: 25%;
    }

    .table th:last-child,
    .table td:last-child {
        width: 25%;
    }

    /* For 2-column tables (interpreters and technicians) */
    .two-column-table th:first-child,
    .two-column-table td:first-child {
        width: 70%;
    }

    .two-column-table th:last-child,
    .two-column-table td:last-child {
        width: 30%;
    }
</style>

<div class="container">
    <h1 class="main-title">Resultado Final - Profissionais Aptos</h1>
    <p class="text-muted">Todos os profissionais aptos credenciados</p>
    <p class="text-muted"><small>Ordenado por data de chamada | Clique nos cabeçalhos para reordenar</small></p>

    <div class="export-buttons">
        <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
        <button class="btn btn-success" onclick="exportToRealExcel()">Exportar Excel</button>
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

    <!-- Interpreters Section -->
    <div class="section-title">
        Intérpretes de Libras
        <span class="teacher-count" id="interpreters-count">Carregando...</span>
    </div>
    <div id="interpreters" class="results-container">
        <div class="loading">Carregando intérpretes...</div>
    </div>

    <!-- Technicians Section -->
    <div class="section-title">
        Apoio Técnico
        <span class="teacher-count" id="technicians-count">Carregando...</span>
    </div>
    <div id="technicians" class="results-container">
        <div class="loading">Carregando técnicos...</div>
    </div>
</div>

<script>
    // Store all data for export
    let allTeachersData = {
        regular: [],
        postg: [],
        interpreters: [],
        technicians: []
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

    // Load interpreters with apto status
    function loadInterpreters() {
        fetch('../backend/api/get_filtered_interpreters.php?status=1')
            .then(response => response.json())
            .then(interpreters => {
                allTeachersData.interpreters = interpreters;
                displayInterpreters(interpreters);
            })
            .catch(error => {
                console.error('Error loading interpreters:', error);
                document.getElementById('interpreters').innerHTML =
                    '<div class="no-teachers">Erro ao carregar intérpretes</div>';
            });
    }

    // Load technicians with apto status
    function loadTechnicians() {
        fetch('../backend/api/get_filtered_technicians.php?status=1')
            .then(response => response.json())
            .then(technicians => {
                allTeachersData.technicians = technicians;
                displayTechnicians(technicians);
            })
            .catch(error => {
                console.error('Error loading technicians:', error);
                document.getElementById('technicians').innerHTML =
                    '<div class="no-teachers">Erro ao carregar técnicos</div>';
            });
    }

    // Display regular teachers grouped by course
    function displayRegularTeachers(teachers) {
        const container = document.getElementById('regular-teachers');
        const courses = <?php echo json_encode($regularCourses); ?>;

        if (teachers.length === 0) {
            container.innerHTML = '<div class="no-teachers">Nenhum docente apto encontrado</div>';
            document.getElementById('regular-count').textContent = '(0 docentes)';
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
                            <th onclick="sortTable(this, 2, 'date')">Inscrito em</th>
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
            document.getElementById('postg-count').textContent = '(0 docentes)';
            return;
        }

        // Group teachers by course
        const teachersByCourse = {};
        teachers.forEach(teacher => {
            if (teacher.discipline_statuses) {
                const statuses = teacher.discipline_statuses.split('||');
                statuses.forEach(statusStr => {
                    const parts = statusStr.split(':');
                    if (parts.length >= 3 && parts[2] === '1') { // Only "apto" (1)
                        const courseId = parts[0];
                        if (!teachersByCourse[courseId]) {
                            teachersByCourse[courseId] = [];
                        }
                        teachersByCourse[courseId].push(teacher);
                    }
                });
            }
        });

        let html = '';
        let totalCount = 0;

        courses.forEach(course => {
            if (teachersByCourse[course.id]) {
                const courseTeachers = teachersByCourse[course.id];
                totalCount += courseTeachers.length;

                html += `
                <div class="course-section">
                    <div class="course-title">${course.name} (${courseTeachers.length} docente${courseTeachers.length > 1 ? 's' : ''})</div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th onclick="sortTable(this, 0, 'text')">Nome</th>
                                <th onclick="sortTable(this, 1, 'date')">Chamado em</th>
                                <th onclick="sortTable(this, 2, 'date')">Inscrito em</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

                courseTeachers.forEach(teacher => {
                    const calledAt = teacher.called_at ?
                        new Date(teacher.called_at).toLocaleDateString('pt-BR') : '---';
                    const createdAt = new Date(teacher.created_at).toLocaleDateString('pt-BR');

                    html += `
                    <tr>
                        <td>${titleCase(teacher.name)}</td>
                        <td>${calledAt}</td>
                        <td>${createdAt}</td>
                    </tr>
                `;
                });

                html += '</tbody></table></div>';
            }
        });

        container.innerHTML = html || '<div class="no-teachers">Nenhum docente apto encontrado</div>';
        document.getElementById('postg-count').textContent = `(${totalCount} docente${totalCount !== 1 ? 's' : ''})`;
    }

    // Display interpreters
    function displayInterpreters(interpreters) {
        const container = document.getElementById('interpreters');

        if (interpreters.length === 0) {
            container.innerHTML = '<div class="no-teachers">Nenhum intérprete apto encontrado</div>';
            document.getElementById('interpreters-count').textContent = '(0 intérpretes)';
            return;
        }

        let html = `
        <table class="table table-striped two-column-table">
            <thead>
                <tr>
                    <th onclick="sortTable(this, 0, 'text')">Nome</th>
                    <th onclick="sortTable(this, 1, 'date')">Inscrito em</th>
                </tr>
            </thead>
            <tbody>
    `;

        interpreters.forEach(interpreter => {
            const createdAt = new Date(interpreter.created_at).toLocaleDateString('pt-BR');

            html += `
            <tr>
                <td>${titleCase(interpreter.name)}</td>
                <td>${createdAt}</td>
            </tr>
        `;
        });

        html += '</tbody></table>';

        container.innerHTML = html;
        document.getElementById('interpreters-count').textContent = `(${interpreters.length} intérprete${interpreters.length !== 1 ? 's' : ''})`;
    }

    // Display technicians
    function displayTechnicians(technicians) {
        const container = document.getElementById('technicians');

        if (technicians.length === 0) {
            container.innerHTML = '<div class="no-teachers">Nenhum técnico apto encontrado</div>';
            document.getElementById('technicians-count').textContent = '(0 técnicos)';
            return;
        }

        let html = `
        <table class="table table-striped two-column-table">
            <thead>
                <tr>
                    <th onclick="sortTable(this, 0, 'text')">Nome</th>
                    <th onclick="sortTable(this, 1, 'date')">Inscrito em</th>
                </tr>
            </thead>
            <tbody>
    `;

        technicians.forEach(technician => {
            const createdAt = new Date(technician.created_at).toLocaleDateString('pt-BR');

            html += `
            <tr>
                <td>${titleCase(technician.name)}</td>
                <td>${createdAt}</td>
            </tr>
        `;
        });

        html += '</tbody></table>';

        container.innerHTML = html;
        document.getElementById('technicians-count').textContent = `(${technicians.length} técnico${technicians.length !== 1 ? 's' : ''})`;
    }

    // Export to Excel/CSV
    function exportToRealExcel() {
    // Check if SheetJS is loaded
    if (typeof XLSX === 'undefined') {
        alert('Erro: Biblioteca de exportação não carregada. Por favor, recarregue a página.');
        return;
    }
    
    // Get PHP data
    const regularCourses = <?php echo json_encode($regularCourses); ?>;
    const postgCourses = <?php echo json_encode($postgCourses); ?>;
    
    // Prepare data arrays for each sheet
    const allProfessionalsData = [];
    const regularTeachersData = [];
    const postgTeachersData = [];
    const interpretersData = [];
    const techniciansData = [];
    
    // Headers for all professionals sheet
    allProfessionalsData.push(['Tipo', 'Curso', 'Nome', 'Chamado em', 'Inscrito em']);
    
    // Headers for individual sheets
    regularTeachersData.push(['Curso', 'Nome', 'Chamado em', 'Inscrito em']);
    postgTeachersData.push(['Curso', 'Nome', 'Chamado em', 'Inscrito em']);
    interpretersData.push(['Nome', 'Inscrito em']);
    techniciansData.push(['Nome', 'Inscrito em']);
    
    // Process regular teachers (Pós-Graduação)
    allTeachersData.regular.forEach(teacher => {
        if (teacher.discipline_statuses) {
            const statuses = teacher.discipline_statuses.split('||');
            const coursesSeen = new Set();
            
            statuses.forEach(statusStr => {
                const parts = statusStr.split(':');
                if (parts.length >= 3 && parts[2] === '1') {
                    const courseId = parts[0];
                    
                    if (!coursesSeen.has(courseId)) {
                        coursesSeen.add(courseId);
                        const courseName = regularCourses.find(c => c.id == courseId)?.name || 'Curso Desconhecido';
                        const calledAt = teacher.called_at ? 
                            new Date(teacher.called_at).toLocaleDateString('pt-BR') : '---';
                        const createdAt = new Date(teacher.created_at).toLocaleDateString('pt-BR');
                        
                        // Add to all professionals sheet
                        allProfessionalsData.push([
                            'Pós-Graduação',
                            courseName,
                            titleCase(teacher.name),
                            calledAt,
                            createdAt
                        ]);
                        
                        // Add to regular teachers sheet
                        regularTeachersData.push([
                            courseName,
                            titleCase(teacher.name),
                            calledAt,
                            createdAt
                        ]);
                    }
                }
            });
        }
    });
    
    // Process post-graduation teachers (Docentes Regulares)
    allTeachersData.postg.forEach(teacher => {
        if (teacher.discipline_statuses) {
            const statuses = teacher.discipline_statuses.split('||');
            const coursesSeen = new Set();
            
            statuses.forEach(statusStr => {
                const parts = statusStr.split(':');
                if (parts.length >= 3 && parts[2] === '1') {
                    const courseId = parts[0];
                    
                    if (!coursesSeen.has(courseId)) {
                        coursesSeen.add(courseId);
                        const courseName = postgCourses.find(c => c.id == courseId)?.name || 'Curso Desconhecido';
                        const calledAt = teacher.called_at ? 
                            new Date(teacher.called_at).toLocaleDateString('pt-BR') : '---';
                        const createdAt = new Date(teacher.created_at).toLocaleDateString('pt-BR');
                        
                        // Add to all professionals sheet
                        allProfessionalsData.push([
                            'Docente Regular',
                            courseName,
                            titleCase(teacher.name),
                            calledAt,
                            createdAt
                        ]);
                        
                        // Add to post-graduation teachers sheet
                        postgTeachersData.push([
                            courseName,
                            titleCase(teacher.name),
                            calledAt,
                            createdAt
                        ]);
                    }
                }
            });
        }
    });
    
    // Process interpreters
    allTeachersData.interpreters.forEach(interpreter => {
        const createdAt = new Date(interpreter.created_at).toLocaleDateString('pt-BR');
        
        // Add to all professionals sheet
        allProfessionalsData.push([
            'Intérprete',
            'N/A',
            titleCase(interpreter.name),
            '---',
            createdAt
        ]);
        
        // Add to interpreters sheet
        interpretersData.push([
            titleCase(interpreter.name),
            createdAt
        ]);
    });
    
    // Process technicians
    allTeachersData.technicians.forEach(technician => {
        const createdAt = new Date(technician.created_at).toLocaleDateString('pt-BR');
        
        // Add to all professionals sheet
        allProfessionalsData.push([
            'Técnico',
            'N/A',
            titleCase(technician.name),
            '---',
            createdAt
        ]);
        
        // Add to technicians sheet
        techniciansData.push([
            titleCase(technician.name),
            createdAt
        ]);
    });
    
    // Create workbook
    const wb = XLSX.utils.book_new();
    
    // Create worksheets
    const wsAll = XLSX.utils.aoa_to_sheet(allProfessionalsData);
    const wsRegular = XLSX.utils.aoa_to_sheet(regularTeachersData);
    const wsPostg = XLSX.utils.aoa_to_sheet(postgTeachersData);
    const wsInterpreters = XLSX.utils.aoa_to_sheet(interpretersData);
    const wsTechnicians = XLSX.utils.aoa_to_sheet(techniciansData);
    
    // Set column widths for all professionals sheet
    wsAll['!cols'] = [
        { wch: 15 }, // Tipo
        { wch: 40 }, // Curso
        { wch: 40 }, // Nome
        { wch: 12 }, // Chamado em
        { wch: 12 }  // Inscrito em
    ];
    
    // Set column widths for teacher sheets
    const teacherCols = [
        { wch: 40 }, // Curso
        { wch: 40 }, // Nome
        { wch: 12 }, // Chamado em
        { wch: 12 }  // Inscrito em
    ];
    wsRegular['!cols'] = teacherCols;
    wsPostg['!cols'] = teacherCols;
    
    // Set column widths for interpreter/technician sheets
    const simpleCols = [
        { wch: 40 }, // Nome
        { wch: 12 }  // Inscrito em
    ];
    wsInterpreters['!cols'] = simpleCols;
    wsTechnicians['!cols'] = simpleCols;
    
    // Add worksheets to workbook
    XLSX.utils.book_append_sheet(wb, wsAll, "Todos Profissionais");
    
    // Only add sheets that have data (more than just headers)
    if (regularTeachersData.length > 1) {
        XLSX.utils.book_append_sheet(wb, wsRegular, "Pós-Graduação");
    }
    if (postgTeachersData.length > 1) {
        XLSX.utils.book_append_sheet(wb, wsPostg, "Docentes Regulares");
    }
    if (interpretersData.length > 1) {
        XLSX.utils.book_append_sheet(wb, wsInterpreters, "Intérpretes");
    }
    if (techniciansData.length > 1) {
        XLSX.utils.book_append_sheet(wb, wsTechnicians, "Técnicos");
    }
    
    // Add summary sheet
    const summaryData = [
        ['Resumo de Profissionais Aptos'],
        [''],
        ['Categoria', 'Quantidade'],
        ['Docentes - Pós-Graduação', regularTeachersData.length - 1],
        ['Docentes - Cursos Regulares', postgTeachersData.length - 1],
        ['Intérpretes de Libras', interpretersData.length - 1],
        ['Apoio Técnico', techniciansData.length - 1],
        [''],
        ['Total Geral', allProfessionalsData.length - 1],
        [''],
        ['Data de Geração', new Date().toLocaleString('pt-BR')]
    ];
    
    const wsSummary = XLSX.utils.aoa_to_sheet(summaryData);
    wsSummary['!cols'] = [
        { wch: 25 },
        { wch: 15 }
    ];
    
    // Style the summary title
    if (wsSummary['A1']) {
        wsSummary['A1'].s = {
            font: { bold: true, sz: 14 }
        };
    }
    
    // Insert summary as first sheet
    XLSX.utils.book_append_sheet(wb, wsSummary, "Resumo", true);
    
    // Generate Excel file
    const fileName = "profissionais_aptos_" + new Date().toISOString().split('T')[0] + ".xlsx";
    
    try {
        XLSX.writeFile(wb, fileName);
        
        // Optional: Show success message
        const exportBtn = document.querySelector('.btn-success');
        const originalText = exportBtn.textContent;
        exportBtn.textContent = '✓ Exportado com sucesso!';
        exportBtn.disabled = true;
        
        setTimeout(() => {
            exportBtn.textContent = originalText;
            exportBtn.disabled = false;
        }, 2000);
        
    } catch (error) {
        console.error('Erro ao exportar:', error);
        alert('Erro ao exportar o arquivo. Por favor, tente novamente.');
    }
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
        loadInterpreters();
        loadTechnicians();
    });
</script>

<?php require_once '../components/footer.php'; ?>