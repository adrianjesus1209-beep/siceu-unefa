<?php
// PANEL DE ESTUDIANTE
if ($_SESSION['rol_usuario'] !== 'Estudiante') {
    header('Location: index.php?page=dashboard');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
?>

<?php
$stmt_docs_notify = $conexion->prepare("SELECT estado, COUNT(*) as total FROM registro_documentos WHERE id_usuario = :id GROUP BY estado");
$stmt_docs_notify->execute([':id' => $_SESSION['id_usuario']]);
$docs_summary = [];
foreach ($stmt_docs_notify->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $docs_summary[$row['estado']] = (int)$row['total'];
}
$tiene_rechazados = ($docs_summary['Rechazado'] ?? 0) > 0;
$tiene_pendientes = ($docs_summary['Pendiente'] ?? 0) > 0;

$stmt_est_notify = $conexion->prepare("SELECT estado FROM usuario WHERE id = :id LIMIT 1");
$stmt_est_notify->execute([':id' => $_SESSION['id_usuario']]);
$estado_cuenta = $stmt_est_notify->fetchColumn();
?>

<?php if ($tiene_rechazados && $estado_cuenta !== 'Aprobado'): ?>
<script>document.addEventListener('DOMContentLoaded',function(){notificar('Tienes <?= $docs_summary['Rechazado'] ?> documento(s) rechazado(s). Sube los documentos corregidos para continuar tu registro.','error');});</script>
<?php elseif ($estado_cuenta === 'Pendiente' && $tiene_pendientes): ?>
<script>document.addEventListener('DOMContentLoaded',function(){notificar('Tus documentos estan siendo revisados por la coordinacion. Te notificaremos cuando esten listos.','warning');});</script>
<?php endif; ?>

<div class="container py-5 mt-4">
    <div class="p-5 mb-5 rounded-4 bg-white shadow-sm border-0 d-flex flex-column flex-md-row align-items-center gap-4 text-center text-md-start" style="border-bottom: 6px solid #003366 !important;">
        <div class="rounded-circle p-4" style="background: #e8eeff; color: #003366; font-size: 3.5rem;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div>
            <div class="badge bg-primary px-3 py-2 rounded-pill mb-2 mb-md-3 text-uppercase" style="background: #003366 !important; letter-spacing: 1px;">Portal Estudiantil</div>
            <h1 class="fw-bold display-6 mb-1">Hola, <?php echo htmlspecialchars($nombre); ?></h1>
            <p class="text-muted lead mb-0">Gestiona tus inscripciones y accede a tu carnet digital.</p>
        </div>
    </div>

    <?php
    $stmtPerEstado = $conexion->query("SELECT estado, nombre FROM periodo_academico ORDER BY FIELD(estado, 'Activo', 'Planificado', 'Finalizado') LIMIT 1");
    $periodoActual = $stmtPerEstado->fetch(PDO::FETCH_ASSOC);
    $periodoPermite = $periodoActual && $periodoActual['estado'] === 'Activo';

    $stmt_mat = $conexion->prepare(
        "SELECT m.nombre_materia, m.codigo_materia, m.semestre, m.uc, s.nombre_seccion, si.nota
        FROM solicitud_inscripcion si
        JOIN seccion s ON si.id_seccion = s.id
        JOIN materia m ON s.id_materia = m.id
        WHERE si.id_estudiante = :id AND si.estado = 'Aceptada' AND si.nota >= 10
        ORDER BY m.semestre IS NULL, m.semestre ASC, m.nombre_materia ASC"
    );
    $stmt_mat->execute([':id' => $id_usuario]);
    $materias = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);

    $materias_por_semestre = [];
    $total_uc = 0;
    $suma_notas = 0;
    $conteo_notas = 0;
    foreach ($materias as $m) {
        $sem = ($m['semestre'] !== null) ? (int)$m['semestre'] : 99;
        $materias_por_semestre[$sem][] = $m;
        $total_uc += intval($m['uc']);
        $suma_notas += intval($m['nota']);
        $conteo_notas++;
    }
    ksort($materias_por_semestre);
    $promedio = $conteo_notas > 0 ? round($suma_notas / $conteo_notas, 2) : 0;

    $etiquetas_semestre = [
        0 => 'CINU', 1 => '1er', 2 => '2do', 3 => '3er',
        4 => '4to', 5 => '5to', 6 => '6to', 7 => '7mo', 8 => '8vo'
    ];
    ?>

    <?php if (!empty($materias)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header border-0 fw-bold py-3 px-4 d-flex align-items-center justify-content-between" style="background:#003366;color:white;">
                    <div>
                        <i class="fas fa-book-open me-2"></i>Mis Materias Aprobadas
                        <span class="badge bg-light text-dark rounded-pill ms-2"><?= count($materias) ?> materias</span>
                    </div>
                    <div class="d-flex gap-3 small">
                        <span><i class="fas fa-star me-1"></i>Promedio: <strong><?= $promedio ?></strong></span>
                        <span><i class="fas fa-layer-group me-1"></i>UC: <strong><?= $total_uc ?></strong></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="small text-muted">
                                <tr>
                                    <th class="ps-4 py-3">Semestre</th>
                                    <th>Materia</th>
                                    <th>Codigo</th>
                                    <th>UC</th>
                                    <th>Seccion</th>
                                    <th class="text-end pe-4">Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materias_por_semestre as $sem => $mats):
                                    $sem_label = $etiquetas_semestre[$sem] ?? ($sem === 99 ? 'Electiva' : "Semestre $sem");
                                    foreach ($mats as $m):
                                ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-light text-muted border fw-normal"><?= $sem_label ?></span></td>
                                    <td class="fw-bold" style="color:#1e293b;font-size:.85rem;"><?= htmlspecialchars($m['nombre_materia']) ?></td>
                                    <td><span class="badge bg-light text-muted border"><?= htmlspecialchars($m['codigo_materia']) ?></span></td>
                                    <td><?= intval($m['uc']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($m['nombre_seccion']) ?></td>
                                    <td class="text-end pe-4">
                                        <span class="fw-bold text-success"><?= intval($m['nota']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($periodoPermite): ?>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="p-4 bg-primary text-white text-center" style="background: #003366 !important;">
                    <i class="fas fa-search-plus fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-0">Solicitar Inscripcion</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small">Busca tus materias y envia una solicitud a tus docentes para inscribirte en la seccion correspondiente.</p>
                    <a href="index.php?page=enrollment" class="btn btn-outline-primary rounded-pill px-4 w-100 fw-bold d-block">Explorar Materias</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden opacity-75">
                <div class="p-4 text-white text-center" style="background: #6b7280 !important;">
                    <i class="fas fa-ban fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-0">Inscripciones Cerradas</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small">El periodo academico <?= $periodoActual ? '«' . htmlspecialchars($periodoActual['nombre']) . '» esta ' . htmlspecialchars($periodoActual['estado']) : 'no esta activo' ?>. Las inscripciones no estan disponibles.</p>
                    <span class="btn btn-secondary rounded-pill px-4 w-100 fw-bold disabled d-block">No disponible</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="p-4 bg-success text-white text-center">
                    <i class="fas fa-qrcode fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-0">Mi Carnet Digital</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small">Muestra tu codigo QR personal para ser identificado rapidamente por el personal universitario.</p>
                    <div class="d-grid gap-2">
                        <a href="index.php?page=mi_qr" class="btn btn-outline-success rounded-pill px-4 fw-bold">Mostrar QR</a>
                        <a href="index.php?page=mi_carnet" class="btn btn-success rounded-pill px-4 fw-bold">Ver Carnet</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="p-4 bg-info text-white text-center text-white" style="background: #17a2b8 !important;">
                    <i class="fas fa-file-invoice fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-0">Record academico</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small">Consulta tu historial de notas, promedio general, progreso por semestre y descarga constancias.</p>
                    <a href="index.php?page=record" class="btn btn-outline-info rounded-pill px-4 w-100 fw-bold">Ver Record</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #0d9488, #0f766e);">
                    <i class="fas fa-file-pdf fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-0">Constancias</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small">Descarga tu constancia de estudio o de inscripcion en formato PDF oficial.</p>
                    <div class="d-grid gap-2">
                        <a href="index.php?action=constancia_estudio&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-outline-teal rounded-pill px-4 fw-bold">Constancia de Estudio</a>
                        <a href="index.php?action=constancia_inscripcion&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-outline-teal rounded-pill px-4 fw-bold">Constancia de Inscripcion</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="p-4 text-white text-center" style="background: #7c3aed;">
                    <i class="fas fa-file-alt fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-0">Planilla de Preinscripcion</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small">Visualiza e imprime tu planilla de preinscripcion registrada en el sistema.</p>
                    <a href="index.php?page=planilla" target="_blank" class="btn btn-outline-primary rounded-pill px-4 w-100 fw-bold" style="border-color:#7c3aed;color:#7c3aed;">Ver Planilla</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="p-4 text-white text-center" style="background: #16a34a;">
                    <i class="fas fa-calendar-week fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-0">Cronograma academico</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small">Consulta el calendario de actividades academicas del periodo vigente.</p>
                    <a href="index.php?page=cronograma" class="btn btn-outline-success rounded-pill px-4 w-100 fw-bold">Ver Cronograma</a>
                </div>
            </div>
        </div>
    </div>
</div>

