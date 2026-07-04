<?php
// PANEL DE DOCENTE
if (!in_array($_SESSION['rol_usuario'], ['Docente', 'Coordinador'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';

$periodoActivo = $conexion->query("SELECT * FROM periodo_academico WHERE estado = 'Activo' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$stmtPerDoc = $conexion->prepare("
    SELECT DISTINCT pa.id, pa.nombre
    FROM periodo_academico pa
    JOIN solicitud_inscripcion si ON si.id_periodo = pa.id
    JOIN seccion s ON si.id_seccion = s.id
    WHERE s.id_docente = :id
    ORDER BY pa.fecha_inicio DESC
");
$stmtPerDoc->execute([':id' => $id_usuario]);
$periodosDocente = $stmtPerDoc->fetchAll(PDO::FETCH_ASSOC);

$filtro_periodo = $_GET['periodo'] ?? '';
if ($filtro_periodo === '' && $periodoActivo) {
    $filtro_periodo = $periodoActivo['id'];
}

$sentencia = $conexion->prepare("
    SELECT s.id, p.nombre, p.apellido, p.cedula, m.nombre_materia, sec.nombre_seccion, s.fecha_solicitud 
    FROM solicitud_inscripcion s
    JOIN usuario u ON s.id_estudiante = u.id
    JOIN perfil p ON u.id_perfil = p.id
    JOIN seccion sec ON s.id_seccion = sec.id
    JOIN materia m ON sec.id_materia = m.id
    WHERE sec.id_docente = :id_docente AND s.estado = 'Pendiente'
    ORDER BY s.fecha_solicitud DESC
");
$sentencia->bindParam(':id_docente', $id_usuario);
$sentencia->execute();
$solicitudes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sentencia = $conexion->prepare("
    SELECT s.id, s.id_estudiante, sec.id_materia,
        p.nombre, p.apellido, p.cedula,
        m.nombre_materia, sec.nombre_seccion,
        s.nota, s.estado, s.ciclo_cerrado, s.id_periodo
    FROM solicitud_inscripcion s
    JOIN usuario u ON s.id_estudiante = u.id
    JOIN perfil p ON u.id_perfil = p.id
    JOIN seccion sec ON s.id_seccion = sec.id
    JOIN materia m ON sec.id_materia = m.id
    WHERE sec.id_docente = :id_docente AND s.estado != 'Pendiente'
    AND (:id_periodo = '' OR s.id_periodo = :id_periodo)
    ORDER BY m.nombre_materia ASC, p.apellido ASC
");
$sentencia->bindParam(':id_docente', $id_usuario);
$sentencia->bindValue(':id_periodo', $filtro_periodo);
$sentencia->execute();
$mis_estudiantes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$total_estudiantes = count($mis_estudiantes);
$total_calificados = count(array_filter($mis_estudiantes, fn($e) => $e['nota'] > 0));

$stmtMaterias = $conexion->prepare("
    SELECT DISTINCT m.nombre_materia, m.codigo_materia, sec.nombre_seccion
    FROM seccion sec
    JOIN materia m ON sec.id_materia = m.id
    WHERE sec.id_docente = :id
    ORDER BY m.nombre_materia
");
$stmtMaterias->execute([':id' => $id_usuario]);
$materias_docente = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="container py-4 mt-5">
    <div class="teacher-card shadow-sm p-4 p-md-5 mb-5 border-start border-5 border-primary animate__fadeInUpCustom">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="rounded-4 p-4 d-flex align-items-center justify-content-center" 
                    style="background: var(--teacher-gradient); width: 80px; height: 80px; box-shadow: 0 8px 20px rgba(123, 31, 162, 0.3);">
                    <i class="fas fa-chalkboard-teacher text-white fa-2x"></i>
                </div>
            </div>
            <div class="col mt-3 mt-md-0">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-light text-primary px-3 py-2 rounded-pill small fw-bold">PORTAL academico</span>
                    <span class="text-muted small"><i class="far fa-calendar-alt me-1"></i>
                        <?php if ($periodoActivo): ?>
                            <?= htmlspecialchars($periodoActivo['nombre']) ?>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1" style="font-size:0.65rem;">Activo</span>
                        <?php else: ?>
                            Sin periodo activo
                        <?php endif; ?>
                    </span>
                </div>
                <h2 class="fw-bold mb-1">¡Hola, Prof. <?= htmlspecialchars($nombre) ?>!</h2>
                <p class="text-muted mb-0">Gestione sus calificaciones y solicitudes de inscripcion de forma eficiente.</p>
                <?php if (!empty($materias_docente)): ?>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <?php foreach ($materias_docente as $mat): ?>
                        <span class="badge bg-light text-primary rounded-pill px-3 py-2 fw-semibold" style="font-size:0.8rem;">
                            <i class="fas fa-book me-1"></i><?= htmlspecialchars($mat['nombre_materia']) ?>
                            <span class="text-muted fw-normal ms-1">· <?= htmlspecialchars($mat['nombre_seccion']) ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-auto d-flex gap-3 mt-3 mt-md-0">
                <a href="index.php?page=mi_carnet_docente" class="btn rounded-pill px-4 fw-bold text-white d-flex align-items-center gap-2 shadow-sm"
                style="height:44px;background:var(--teacher-gradient);border:none;font-size:0.875rem;"
                onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                    <i class="fas fa-id-card"></i>
                    Ver Carnet
                </a>
                <a href="index.php?page=cronograma" class="btn rounded-pill px-4 fw-bold text-white d-flex align-items-center gap-2 shadow-sm"
                style="height:44px;background:var(--teacher-gradient);border:none;font-size:0.875rem;"
                onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                    <i class="fas fa-calendar-week"></i>
                    Cronograma
                </a>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="page" value="dashboard">
            <label class="text-muted small fw-bold"><i class="fas fa-filter me-1"></i>Periodo:</label>
            <select name="periodo" class="form-select form-select-sm" style="width:auto;min-width:140px;" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($periodosDocente as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filtro_periodo == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Filtrar</button></noscript>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div class="teacher-card shadow-sm p-4 border-0 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="rounded-3 p-3 bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-bell fa-lg"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= count($solicitudes) ?></h3>
                <p class="text-muted small mb-0 fw-semibold text-uppercase">Solicitudes Pendientes</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="teacher-card shadow-sm p-4 border-0 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= count($mis_estudiantes) ?></h3>
                <p class="text-muted small mb-0 fw-semibold text-uppercase">Estudiantes en mis Secciones</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="teacher-card shadow-sm p-4 border-0 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-double fa-lg"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= $total_calificados ?></h3>
                <p class="text-muted small mb-0 fw-semibold text-uppercase">Notas Cargadas</p>
            </div>
        </div>
    </div>

    <?php if (!empty($solicitudes)): ?>
    <div class="mb-5 animate__fadeInUpCustom" style="animation-delay: 0.2s;">
        <div class="d-flex align-items-center gap-3 mb-4">
            <h4 class="fw-bold mb-0"><i class="fas fa-id-card-alt me-2 text-danger"></i>Inscripciones por Aprobar</h4>
            <div class="flex-grow-1 bg-light" style="height: 2px;"></div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($solicitudes as $sol): ?>
            <div class="col-xl-4 col-md-6">
                <div class="teacher-card shadow-sm p-3 border-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar-icon"><?= mb_strtoupper(mb_substr($sol['nombre'],0,1).mb_substr($sol['apellido'],0,1), 'UTF-8') ?></div>
                        <div class="overflow-hidden">
                            <h6 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars($sol['nombre'].' '.$sol['apellido']) ?></h6>
                            <span class="text-muted small">C.I. <?= htmlspecialchars($sol['cedula']) ?></span>
                        </div>
                    </div>
                    <div class="bg-light rounded-3 p-2 mb-3">
                        <div class="small fw-bold text-dark text-truncate"><?= htmlspecialchars($sol['nombre_materia']) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;">Seccion <?= htmlspecialchars($sol['nombre_seccion']) ?></div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php?action=handle_request&id=<?= $sol['id'] ?>&status=Aprobada&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                        class="btn btn-success btn-sm flex-grow-1 rounded-3">
                            <i class="fas fa-check me-1"></i>Aceptar
                        </a>
                        <a href="index.php?action=handle_request&id=<?= $sol['id'] ?>&status=Rechazada&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                        class="btn btn-outline-danger btn-sm flex-grow-1 rounded-3">
                            <i class="fas fa-times me-1"></i>Rechazar
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="animate__fadeInUpCustom" style="animation-delay: 0.4s;">
        <div class="d-flex align-items-center gap-3 mb-4">
            <h4 class="fw-bold mb-0"><i class="fas fa-graduation-cap me-2 text-primary"></i>Listas de Estudiantes</h4>
            <div class="flex-grow-1 bg-light" style="height: 2px;"></div>
        </div>

        <?php 
        $agrupados = [];
        foreach ($mis_estudiantes as $est) {
            $key = $est['nombre_materia'] . ' - Seccion ' . $est['nombre_seccion'];
            $agrupados[$key][] = $est;
        }
        ?>

        <?php if (empty($agrupados)): ?>
            <div class="teacher-card shadow-sm p-5 text-center border-0">
                <i class="fas fa-users-slash fa-4x text-muted mb-4 opacity-25"></i>
                <h5 class="text-muted">Aun no tiene estudiantes inscritos en sus materias.</h5>
            </div>
        <?php else: ?>
            <?php foreach ($agrupados as $seccion => $estudiantes): ?>
            <div class="teacher-card shadow-sm border-0 mb-5 overflow-hidden">
                <?php
                    $id_materia = $estudiantes[0]['id_materia'];
                    $periodo_id = $estudiantes[0]['id_periodo'];
                    $todas_notas = array_filter($estudiantes, fn($e) => $e['nota'] > 0);
                    $ciclo_cerrado = !empty($estudiantes) && !empty(array_filter($estudiantes, fn($e) => $e['ciclo_cerrado']));
                    $total_est = count($estudiantes);
                    $calificados_est = count($todas_notas);
                    $pct = $total_est > 0 ? round($calificados_est / $total_est * 100) : 0;
                ?>
                <div class="card-header border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-2 card-header-docente">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($seccion) ?></h5>
                        <p class="text-muted small mb-0 fw-semibold"><i class="fas fa-users me-1"></i><?= $total_est ?> Estudiantes</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php?action=download_teacher_report&materia=<?= $id_materia ?>&periodo=<?= $periodo_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                        class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" title="Descargar reporte PDF">
                            <i class="fas fa-download me-1"></i>Reporte
                        </a>
                        <?php if ($ciclo_cerrado): ?>
                            <span class="badge bg-success rounded-pill px-3 py-2" title="Todos los estudiantes han sido calificados y el ciclo esta cerrado.">
                                <i class="fas fa-check-circle me-1"></i>Ciclo Cerrado
                            </span>
                        <?php elseif ($calificados_est === $total_est): ?>
                            <a href="index.php?action=close_cycle&materia=<?= $id_materia ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                            class="btn btn-sm rounded-pill px-3 fw-bold text-white" style="background:#7b1fa2;border:none;"
                                                            onclick="event.preventDefault();confirmar('¿Cerrar ciclo de esta seccion? Los estudiantes no podran modificarse despues.').then(r=>{if(r)location.href=this.href})">
                                <i class="fas fa-lock me-1"></i>Cerrar Ciclo
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-light btn-sm rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= md5($seccion) ?>">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <?php if (!$ciclo_cerrado): ?>
                <div class="px-4 py-2 bg-light border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-medium">Progreso de calificaciones</small>
                        <small class="fw-bold"><?= $calificados_est ?>/<?= $total_est ?> calificados</small>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar" role="progressbar" style="width:<?= $pct ?>%;background:<?= $pct === 100 ? '#16a34a' : '#7b1fa2' ?>;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="collapse show" id="collapse<?= md5($seccion) ?>">
                    <div class="table-responsive p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small text-uppercase">
                                    <th class="ps-4 py-3">Estudiante</th>
                                    <th>Cedula</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-4" style="width: 200px;">Calificacion Final</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estudiantes as $e): 
                                    $is_reprobado = ($e['nota'] < 10 && $e['nota'] > 0);
                                    $has_nota = ($e['nota'] > 0);
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-icon" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                                <?= mb_strtoupper(mb_substr($e['nombre'],0,1).mb_substr($e['apellido'],0,1), 'UTF-8') ?>
                                            </div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($e['nombre'].' '.$e['apellido']) ?></div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-muted border fw-normal"><?= htmlspecialchars($e['cedula']) ?></span></td>
                                    <td>
                                        <?php if ($has_nota): ?>
                                            <span class="status-badge <?= $is_reprobado ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                                                <?= $is_reprobado ? 'Reprobado' : 'Aprobado' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge bg-warning-subtle text-warning">Cursando</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 py-3">
                                        <?php if ($e['ciclo_cerrado']): ?>
                                            <div class="d-flex justify-content-end align-items-center gap-2">
                                                <span class="badge bg-light text-muted border rounded-pill px-3 py-2 fw-bold"><?= $e['nota'] ?: '—' ?></span>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 small" style="font-size:0.65rem;"><i class="fas fa-lock me-1"></i>Cerrado</span>
                                            </div>
                                        <?php else: ?>
                                        <form action="index.php?action=save_grade" method="POST" class="d-flex gap-2 justify-content-end">
                                            <input type="hidden" name="id_solicitud" value="<?= $e['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="number" name="nota" min="0" max="20" step="1" 
                                                class="form-control form-control-sm text-center fw-bold grade-input" 
                                                value="<?= $e['nota'] ?: '' ?>" placeholder="--" required>
                                            <button type="submit" class="btn btn-sm btn-primary rounded-3 px-3 shadow-sm" style="background:#7b1fa2; border:none; transition: all 0.2s;">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

