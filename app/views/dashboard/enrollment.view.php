<?php
// INSCRIPCION DE MATERIAS
if ($_SESSION['rol_usuario'] !== 'Estudiante') {
    header('Location: index.php?page=dashboard');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$stmtPer = $conexion->query("SELECT estado, nombre FROM periodo_academico ORDER BY FIELD(estado, 'Activo', 'Planificado', 'Finalizado') LIMIT 1");
$periodoActual = $stmtPer->fetch(PDO::FETCH_ASSOC);
$periodoPermite = $periodoActual && $periodoActual['estado'] === 'Activo';

$stmt = $conexion->prepare("
    SELECT p.*, c.nombre_carrera 
    FROM perfil p 
    JOIN usuario u ON p.id = u.id_perfil 
    LEFT JOIN carrera c ON p.id_carrera = c.id 
    WHERE u.id = :id
");
$stmt->execute([':id' => $id_usuario]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

$materias = $conexion->query("SELECT * FROM materia ORDER BY semestre IS NULL, semestre ASC, orden ASC, nombre_materia ASC")->fetchAll(PDO::FETCH_ASSOC);

$secciones_por_materia = [];
$stmtSec = $conexion->prepare("
    SELECT sec.*, p.nombre as docente_nombre, p.apellido as docente_apellido
    FROM seccion sec
    JOIN usuario u ON sec.id_docente = u.id
    JOIN perfil p ON u.id_perfil = p.id
    WHERE sec.id_materia = :id AND u.rol = 'Docente'
");
foreach ($materias as $m) {
    $stmtSec->execute([':id' => $m['id']]);
    $secciones_por_materia[$m['id']] = $stmtSec->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $conexion->prepare("SELECT * FROM solicitud_inscripcion WHERE id_estudiante = :id");
$stmt->execute([':id' => $id_usuario]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estado_solicitud = [];
$materia_inscrita = [];
$stats = ['total' => 0, 'pendiente' => 0, 'aceptada' => 0, 'rechazada' => 0];

foreach ($solicitudes as $s) {
    $estado_solicitud[$s['id_seccion']] = $s['estado'];
    $stats['total']++;
    $stats[strtolower($s['estado'])]++;
    
    foreach ($secciones_por_materia as $mid => $secs) {
        foreach ($secs as $sec) {
            if ($sec['id'] == $s['id_seccion']) {
                $materia_inscrita[$mid] = $s['estado'];
            }
        }
    }
}

$prelaciones_raw = $conexion->query("SELECT id_materia, id_prerrequisito FROM prelacion")->fetchAll(PDO::FETCH_ASSOC);
$mapa_prelaciones = [];
foreach ($prelaciones_raw as $p) {
    $mapa_prelaciones[$p['id_materia']][] = (int)$p['id_prerrequisito'];
}

$etiquetas_semestre = [
    0 => 'CINU', 1 => '1ro', 2 => '2do', 3 => '3ro',
    4 => '4to', 5 => '5to', 6 => '6to', 7 => '7mo', 8 => '8vo'
];

$total_materias = $conexion->query("SELECT COUNT(*) FROM materia WHERE semestre IS NOT NULL")->fetchColumn();

$defensa_materias = [];
$materias_por_semestre = [];
foreach ($materias as $m) {
    $sem = ($m['semestre'] !== null) ? (int)$m['semestre'] : null;
    if ($sem === null) continue;
    if (strpos($m['codigo_materia'], 'DEF-') === 0) {
        $defensa_materias[$sem] = $m;
        continue;
    }
    $materias_por_semestre[$sem][] = $m;
}

$ids_materias_aceptadas = [];
foreach ($solicitudes as $s) {
    if ($s['estado'] === 'Aceptada' && $s['nota'] >= 10) {
        foreach ($secciones_por_materia as $mid => $secs) {
            foreach ($secs as $sec) {
                if ($sec['id'] == $s['id_seccion']) {
                    $ids_materias_aceptadas[] = $mid;
                }
            }
        }
    }
}
$ids_materias_aceptadas = array_unique($ids_materias_aceptadas);

$semestre_desbloqueado = 0;
foreach ($etiquetas_semestre as $sem_num => $sem_label) {
    if ($sem_num === 0) continue;
    if (!isset($materias_por_semestre[$sem_num - 1])) continue;
    $ids_sem_anterior = array_column($materias_por_semestre[$sem_num - 1], 'id');
    if (empty($ids_sem_anterior)) continue;
    $completado = count(array_intersect($ids_sem_anterior, $ids_materias_aceptadas)) >= count($ids_sem_anterior);
    if ($completado) {
        $semestre_desbloqueado = $sem_num;
    } else {
        break;
    }
}
$semestre_max_aprobado = 0;
foreach ($ids_materias_aceptadas as $mid) {
    foreach ($materias as $m) {
        if ($m['id'] == $mid && $m['semestre'] !== null) {
            $sem = (int)$m['semestre'];
            if ($sem > $semestre_max_aprobado) $semestre_max_aprobado = $sem;
        }
    }
}
foreach ($etiquetas_semestre as $sem_num => $sem_label) {
    if (!isset($materias_por_semestre[$sem_num])) continue;
    $ids_sem = array_column($materias_por_semestre[$sem_num], 'id');
    if (count(array_intersect($ids_sem, $ids_materias_aceptadas)) >= count($ids_sem)) {
        if ($sem_num + 1 > $semestre_desbloqueado) {
            $semestre_desbloqueado = $sem_num + 1;
        }
    }
}
if ($semestre_max_aprobado > $semestre_desbloqueado) {
    $semestre_desbloqueado = $semestre_max_aprobado;
}
$semestre_desbloqueado = min($semestre_desbloqueado, 8);

$photoUrl = !empty($perfil['foto_perfil']) 
    ? URLROOT . '/uploads/profiles/' . rawurlencode($perfil['foto_perfil']) 
    : 'https://ui-avatars.com/api/?name='.urlencode($perfil['nombre']).'&background=003366&color=fff';
?>

<style>
.defensa-section {
    margin-top: 1.5rem;
}
.defensa-grid {
    display: flex;
    gap: 0.6rem;
    min-width: max-content;
    padding-bottom: 1rem;
}
</style>

<div class="container-fluid py-4 unefa-zoom-10 px-5">
    
    <div class="identity-card animate__animated animate__fadeInDown p-3 py-2 mb-3">
        <img src="<?= $photoUrl ?>" alt="Foto del Estudiante" class="student-avatar" style="width:55px; height:55px;">
        <div class="flex-grow-1">
            <h4 class="fw-black mb-0" style="color:var(--unefa-blue); line-height:1;"><?= $perfil['nombre'] ?> <?= $perfil['apellido'] ?></h4>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="text-primary fw-bold" style="font-size:0.8rem;"><?= ($perfil['tipo_documento'] ?? 'V') . '-' . $perfil['cedula'] ?></span>
                <span class="text-muted small"><i class="fas fa-graduation-cap me-1"></i><?= $perfil['nombre_carrera'] ?? 'Sin Carrera' ?></span>
                <?php if ($semestre_max_aprobado > 0): ?>
                <span class="badge rounded-pill fw-bold px-3 py-2" style="background:#003366;color:#fff;font-size:.7rem;">
                    <i class="fas fa-layer-group me-1"></i><?= $etiquetas_semestre[$semestre_max_aprobado] ?? "Semestre $semestre_max_aprobado" ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <div class="p-2 px-3 bg-light rounded-pill border text-end">
                <small class="d-block text-muted" style="font-size:0.6rem; text-transform:uppercase;">Materias</small>
                <span class="fw-bold text-primary"><?= $stats['aceptada'] + $stats['pendiente'] ?> <small class="text-muted">/ <?= $total_materias ?></small></span>
            </div>
            <?php
                $sql_uc = "SELECT SUM(m.uc) as current_uc FROM solicitud_inscripcion si JOIN seccion s ON si.id_seccion = s.id JOIN materia m ON s.id_materia = m.id WHERE si.id_estudiante = ? AND si.estado IN ('Pendiente', 'Aceptada')";
                $stmt_uc = $conexion->prepare($sql_uc);
                $stmt_uc->execute([$_SESSION['id_usuario']]);
                $current_uc = (int)$stmt_uc->fetchColumn();
            ?>
            <div class="p-2 px-3 bg-light rounded-pill border text-end">
                <small class="d-block text-muted" style="font-size:0.6rem; text-transform:uppercase;">Creditos (UC)</small>
                <span class="fw-bold text-success"><?= $current_uc ?> <small class="text-muted">/ 18</small></span>
            </div>
        </div>
    </div>

    <?php if (!$periodoPermite): ?>
    <div class="alert alert-warning rounded-4 shadow-sm mb-4 border-0 d-flex align-items-center gap-3" style="border-left: 5px solid #f59e0b !important;">
        <i class="fas fa-exclamation-triangle fa-lg" style="color:#d97706;"></i>
        <div>
            <strong class="fw-bold">Inscripciones cerradas</strong><br>
            <span class="small">El periodo academico <?= $periodoActual ? '«' . htmlspecialchars($periodoActual['nombre']) . '» esta ' . htmlspecialchars($periodoActual['estado']) : 'no esta activo' ?>. No puedes realizar nuevas inscripciones en este momento.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="mb-3">
        <h4 class="fw-bold mb-0">Mi Inscripcion</h4>
        <p class="text-muted small mb-3">Completa cada semestre para desbloquear el siguiente. Haz clic en una materia disponible para inscribirte.</p>

        <div class="semester-steps">
            <?php foreach ($etiquetas_semestre as $sem_num => $sem_label): ?>
            <?php
                $step_state = '';
                if ($sem_num === 0 && $semestre_desbloqueado > 0) $step_state = 'done';
                elseif ($sem_num < $semestre_desbloqueado) $step_state = 'done';
                elseif ($sem_num === $semestre_desbloqueado) $step_state = 'active';
                else $step_state = 'locked';
            ?>
            <div class="sem-step sem-step--<?= $step_state ?>" onclick="scrollToSemester(<?= $sem_num ?>)" title="Semestre <?= $sem_label ?>">
                <div class="sem-step__bubble">
                    <?php if ($step_state === 'done'): ?><i class="fas fa-check"></i>
                    <?php elseif ($step_state === 'locked'): ?><i class="fas fa-lock"></i>
                    <?php else: ?><?= $sem_label ?><?php endif; ?>
                </div>
                <div class="sem-step__label"><?= $sem_label ?></div>
            </div>
            <?php if ($sem_num < 8): ?><div class="sem-step__connector <?= $step_state === 'done' ? 'done' : '' ?>"></div><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="pensum-matrix-container">
        <div class="pensum-matrix">
            <?php foreach ($etiquetas_semestre as $index => $label): ?>
                <?php 
                    $col_state = '';
                    if ($index === 0 && $semestre_desbloqueado > 0) $col_state = 'done';
                    elseif ($index < $semestre_desbloqueado) $col_state = 'done';
                    elseif ($index === $semestre_desbloqueado) $col_state = 'active';
                    else $col_state = 'locked';
                ?>
                <div class="semester-column semester-column--<?= $col_state ?>" id="sem-col-<?= $index ?>">
                    <div class="sem-header sem-header--<?= $col_state ?>">
                        <?php if ($col_state === 'done'): ?><i class="fas fa-check-circle me-1"></i><?php endif; ?>
                        <?php if ($col_state === 'locked'): ?><i class="fas fa-lock me-1"></i><?php endif; ?>
                        <?= $label ?>
                    </div>
                    <?php if (isset($materias_por_semestre[$index])): ?>
                        <?php foreach ($materias_por_semestre[$index] as $m):
                            $status_class = '';
                            $status = $materia_inscrita[$m['id']] ?? null;
                            if ($status == 'Aceptada')       $status_class = 'materia-aceptada';
                            elseif ($status == 'Pendiente') $status_class = 'materia-pendiente';
                            elseif ($status == 'Rechazada') $status_class = 'materia-rechazada';
                        ?>
                            <?php if ($col_state === 'locked'): ?>
                                <div class="subject-card-compact subject-card--locked"
                                    id="subj-<?= $m['id'] ?>"
                                    title="Completa el semestre anterior para desbloquear esta materia"
                                    onmouseover="highlightDependencies(<?= $m['id'] ?>)"
                                    onmouseout="clearHighlights()">
                                    <span class="code"><?= $m['codigo_materia'] ?></span>
                                    <span class="name"><?= $m['nombre_materia'] ?></span>
                                    <span class="lock-badge"><i class="fas fa-lock"></i></span>
                                </div>
                            <?php else: ?>
                                <div class="subject-card-compact <?= $status_class ?>"
                                    id="subj-<?= $m['id'] ?>"
                                    onclick='openMateria(<?= json_encode((int)$m['id']) ?>, <?= json_encode($m['codigo_materia']) ?>, <?= json_encode($m['nombre_materia']) ?>)'
                                    onmouseover="highlightDependencies(<?= $m['id'] ?>)"
                                    onmouseout="clearHighlights()">
                                    <span class="code"><?= $m['codigo_materia'] ?></span>
                                    <span class="name"><?= $m['nombre_materia'] ?></span>
                                    <?php if ($status == 'Aceptada'): ?><span class="done-badge"><i class="fas fa-check"></i></span><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    $romanos = [0 => 'CINU', 1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII'];
    ?>
    <div class="defensa-section">
        <div class="defensa-grid">
            <?php foreach ($romanos as $sem => $rom): ?>
                <?php $m = $defensa_materias[$sem] ?? null; if (!$m) continue; ?>
                <?php
                    $col_state = '';
                    if ($sem < $semestre_desbloqueado) $col_state = 'done';
                    elseif ($sem === $semestre_desbloqueado) $col_state = 'active';
                    else $col_state = 'locked';

                    $status = $materia_inscrita[$m['id']] ?? null;
                    $status_class = '';
                    if ($status == 'Aceptada')       $status_class = 'materia-aceptada';
                    elseif ($status == 'Pendiente') $status_class = 'materia-pendiente';
                    elseif ($status == 'Rechazada') $status_class = 'materia-rechazada';

                    $subj_id = $m['id'];
                    $codigo = $m['codigo_materia'];
                    $nombre = $m['nombre_materia'];
                ?>
                <div style="width:155px;flex-shrink:0;">
                <?php if ($col_state === 'locked'): ?>
                <div class="subject-card-compact subject-card--locked"
                    id="subj-<?= $subj_id ?>"
                    title="Completa el semestre anterior para desbloquear esta materia"
                    onmouseover="highlightDependencies(<?= $subj_id ?>)"
                    onmouseout="clearHighlights()">
                    <span class="code"><?= $rom ?></span>
                    <span class="name"><?= htmlspecialchars($nombre) ?></span>
                    <span class="lock-badge"><i class="fas fa-lock"></i></span>
                </div>
                <?php else: ?>
                <div class="subject-card-compact <?= $status_class ?>"
                    id="subj-<?= $subj_id ?>"
                    onclick="openMateria(<?= $subj_id ?>, '<?= htmlspecialchars($codigo, ENT_QUOTES) ?>', '<?= htmlspecialchars($nombre, ENT_QUOTES) ?>')"
                    onmouseover="highlightDependencies(<?= $subj_id ?>)"
                    onmouseout="clearHighlights()">
                    <span class="code"><?= $rom ?></span>
                    <span class="name"><?= htmlspecialchars($nombre) ?></span>
                    <?php if ($status == 'Aceptada'): ?><span class="done-badge"><i class="fas fa-check"></i></span><?php endif; ?>
                </div>
                <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="legend-bar shadow-lg">
    <div class="legend-item"><div class="legend-dot" style="background:var(--unefa-gold);"></div> SELECCIONADA</div>
    <div class="legend-item"><div class="legend-dot" style="background:#ef4444;"></div> PRELACION (REQUERIDO)</div>
    <div class="legend-item"><div class="legend-dot" style="background:#10b981;"></div> LO QUE SIGUE (DESBLOQUEA)</div>
    <div class="legend-item"><div class="legend-dot" style="background:#eab308; border:1px solid #ca8a04;"></div> EN REVISION</div>
</div>

<div class="drawer-overlay" id="overlay" onclick="closeDrawer()"></div>
<div id="sectionDrawer">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <button class="btn btn-light rounded-circle shadow-sm" onclick="closeDrawer()"><i class="fas fa-times"></i></button>
    </div>
    <div id="drawerContent">
    </div>
</div>

<script>
const mapPrelaciones = <?= json_encode($mapa_prelaciones) ?>;
const seccionesMateria = <?= json_encode($secciones_por_materia) ?>;
const estadoSolicitud = <?= json_encode($estado_solicitud) ?>;
const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
const periodoPermite = <?= json_encode($periodoPermite) ?>;

function scrollToSemester(semNum) {
    const col = document.getElementById('sem-col-' + semNum);
    if (col) {
        col.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
}

function highlightDependencies(subjectId) {
    const container = document.querySelector('.pensum-matrix');
    container.classList.add('matrix-dimmed');
    
    const target = document.getElementById('subj-' + subjectId);
    target.classList.add('subj-active');

    if (mapPrelaciones[subjectId]) {
        mapPrelaciones[subjectId].forEach(preId => {
            const el = document.getElementById('subj-' + preId);
            if (el) el.classList.add('subj-highlight-pre');
        });
    }
    Object.keys(mapPrelaciones).forEach(id => {
        if (mapPrelaciones[id].includes(subjectId)) {
            const el = document.getElementById('subj-' + id);
            if (el) el.classList.add('subj-highlight-next');
        }
    });
}

function clearHighlights() {
    const container = document.querySelector('.pensum-matrix');
    container.classList.remove('matrix-dimmed');
    
    document.querySelectorAll('.subject-card-compact').forEach(el => {
        el.classList.remove('subj-highlight-pre', 'subj-highlight-next', 'subj-active');
    });
}

function openMateria(id, codigo, nombre) {
    const drawer = document.getElementById('sectionDrawer');
    const overlay = document.getElementById('overlay');
    const content = document.getElementById('drawerContent');
    const secciones = seccionesMateria[id] || [];
    
    let html = `
        <div class="mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 rounded-pill px-3">${codigo}</span>
            <h2 class="fw-black mb-1 text-dark" style="font-size:1.8rem;">${nombre}</h2>
            <hr class="opacity-10">
        </div>
        <h5 class="fw-bold mb-4 d-flex align-items-center"><i class="fas fa-layer-group me-2 text-primary"></i> Secciones Disponibles</h5>
    `;
    
    if (secciones.length === 0) {
        html += `
            <div class="text-center py-5">
                <i class="fas fa-chalkboard-teacher fa-3x text-muted opacity-20 mb-3"></i>
                <div class="text-muted fw-bold">No hay un docente asignado a esta asignatura</div>
                <div class="text-muted small">Por lo cual no se puede inscribir en este momento.</div>
            </div>
        `;
    } else {
        secciones.forEach(sec => {
            const estado = estadoSolicitud[sec.id] || null;
            let actionHtml = '';
            let statusCardClass = '';

            if (estado === 'Aceptada') {
                statusCardClass = 'border-success bg-success bg-opacity-10';
                actionHtml = '<span class="badge bg-success text-white py-2 rounded-pill px-3 shadow-sm"><i class="fas fa-check me-1"></i>Inscrito</span>';
            } else if (estado === 'Pendiente') {
                statusCardClass = 'border-warning bg-warning bg-opacity-10';
                actionHtml = '<span class="badge bg-warning text-dark py-2 rounded-pill px-3 shadow-sm"><i class="fas fa-clock me-1"></i>En Revision</span>';
            } else if (!periodoPermite) {
                statusCardClass = 'border-secondary bg-secondary bg-opacity-10';
                actionHtml = '<span class="badge bg-secondary text-white py-2 rounded-pill px-3 shadow-sm"><i class="fas fa-ban me-1"></i>Inscripciones cerradas</span>';
            } else {
                actionHtml = `
                    <a href="index.php?action=enroll_subject&seccion=${sec.id}&csrf_token=${csrfToken}" 
                    class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm"
                    onclick="event.preventDefault(); (async () => { if (await confirmar('¿Inscribir?')) window.location.href = this.href; })()">
                    Inscribir
                    </a>
                `;
            }
            
            html += `
                <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden ${statusCardClass}" style="transition:0.3s;">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-black h4 mb-1 text-primary">${sec.nombre_seccion}</div>
                            <div class="text-muted small d-flex align-items-center gap-2">
                                <i class="fas fa-user-tie"></i> 
                                <span class="fw-bold">${sec.docente_nombre} ${sec.docente_apellido}</span>
                            </div>
                        </div>
                        <div>${actionHtml}</div>
                    </div>
                </div>
            `;
        });
    }
    content.innerHTML = html;
    drawer.classList.add('active');
    overlay.classList.add('active');
}

function closeDrawer() {
    document.getElementById('sectionDrawer').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.pensum-matrix-container');
    let isDown = false;
    let startX;
    let scrollLeft;

    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        slider.style.cursor = 'grabbing';
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });
    slider.addEventListener('mouseleave', () => { isDown = false; slider.style.cursor = 'grab'; });
    slider.addEventListener('mouseup', () => { isDown = false; slider.style.cursor = 'grab'; });
    slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2;
        slider.scrollLeft = scrollLeft - walk;
    });
});
</script>

