<?php
// HISTORIAL ACADEMICO
if ($_SESSION['rol_usuario'] !== 'Estudiante') {
    header('Location: index.php?page=dashboard');
    exit;
}
$id_usuario = $_SESSION['id_usuario'];

$stmt = $conexion->prepare(
    "SELECT p.*, c.nombre_carrera, u.estado
    FROM perfil p
    JOIN usuario u ON p.id = u.id_perfil
    LEFT JOIN carrera c ON p.id_carrera = c.id
    WHERE u.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id_usuario]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt_mat = $conexion->prepare(
    "SELECT m.nombre_materia, m.codigo_materia, m.semestre, m.uc, s.nombre_seccion, si.nota, si.estado
    FROM solicitud_inscripcion si
    JOIN seccion s ON si.id_seccion = s.id
    JOIN materia m ON s.id_materia = m.id
    WHERE si.id_estudiante = :id
    ORDER BY m.semestre IS NULL, m.semestre ASC, m.nombre_materia ASC"
);
$stmt_mat->execute([':id' => $id_usuario]);
$materias = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);

$materias_por_semestre = [];
$total_uc_aprobadas = 0;
$total_uc_cursadas = 0;
$suma_notas = 0;
$conteo_notas = 0;
foreach ($materias as $m) {
    $sem = ($m['semestre'] !== null) ? (int)$m['semestre'] : 99;
    $materias_por_semestre[$sem][] = $m;
    $total_uc_cursadas += intval($m['uc']);
    if ($m['estado'] === 'Aceptada' && $m['nota'] >= 10) {
        $total_uc_aprobadas += intval($m['uc']);
        $suma_notas += intval($m['nota']);
        $conteo_notas++;
    }
}
$promedio = $conteo_notas > 0 ? round($suma_notas / $conteo_notas, 2) : 0;

$total_uc_pensum = (int)$conexion->query("SELECT SUM(uc) FROM materia WHERE semestre IS NOT NULL")->fetchColumn();

$etiquetas_semestre = [
    0 => 'CINU', 1 => '1er', 2 => '2do', 3 => '3er',
    4 => '4to', 5 => '5to', 6 => '6to', 7 => '7mo', 8 => '8vo'
];
ksort($materias_por_semestre);

$photoUrl = !empty($perfil['foto_perfil'])
    ? URLROOT . '/uploads/profiles/' . rawurlencode($perfil['foto_perfil'])
    : 'https://ui-avatars.com/api/?name='.urlencode($perfil['nombre']).'&background=003366&color=fff';
?>
<div class="container py-4 mt-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-3 d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#003366,#005c99);width:52px;height:52px;">
                <i class="fas fa-book-open text-white fa-lg"></i>
            </div>
            <div>
                <h1 class="fw-bold mb-0" style="color:#003366;font-size:1.65rem;">Record academico</h1>
                <p class="text-muted mb-0 small">Historial de materias cursadas y rendimiento general</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?action=constancia_estudio&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-outline-primary rounded-pill px-3 fw-bold shadow-sm" style="font-size:.85rem;">
                <i class="fas fa-file-pdf me-1"></i> Constancia de Estudio
            </a>
            <a href="index.php?action=constancia_inscripcion&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-outline-success rounded-pill px-3 fw-bold shadow-sm" style="font-size:.85rem;">
                <i class="fas fa-file-pdf me-1"></i> Constancia de Inscripcion
            </a>
            <a href="index.php?page=dashboard" class="btn rounded-pill px-3 fw-bold shadow-sm" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;font-size:.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="card border-0 rounded-4 mb-4 overflow-hidden shadow-sm">
        <div class="p-4 d-flex align-items-center gap-4" style="background:linear-gradient(135deg,#f8faff,#eef4ff);">
            <img src="<?= $photoUrl ?>" alt="Foto" class="rounded-circle border shadow-sm" style="width:64px;height:64px;object-fit:cover;border:3px solid white !important;">
            <div>
                <h4 class="fw-bold mb-1" style="color:#003366;"><?= htmlspecialchars(trim("{$perfil['nombre']} {$perfil['segundo_nombre']} {$perfil['apellido']} {$perfil['segundo_apellido']}")) ?></h4>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span><i class="fas fa-id-card me-1"></i><?= ($perfil['tipo_documento'] ?? 'V') . '-' . $perfil['cedula'] ?></span>
                    <span><i class="fas fa-graduation-cap me-1"></i><?= htmlspecialchars($perfil['nombre_carrera'] ?? 'Sin carrera') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Promedio General</div>
                    <div class="fw-black" style="font-size:2rem;color:#003366;"><?= $promedio ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small fw-bold text-uppercase mb-1">UC Aprobadas</div>
                    <div class="fw-black" style="font-size:2rem;color:#16a34a;"><?= $total_uc_aprobadas ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small fw-bold text-uppercase mb-1">UC Cursadas</div>
                    <div class="fw-black" style="font-size:2rem;color:#ca8a04;"><?= $total_uc_cursadas ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Progreso</div>
                    <div class="fw-black" style="font-size:2rem;color:#0891b2;">
                        <?= $total_uc_pensum > 0 ? round(($total_uc_aprobadas / $total_uc_pensum) * 100) : 0 ?>%
                    </div>
                    <div class="progress mt-2" style="height:6px;">
                        <div class="progress-bar bg-info" style="width:<?= $total_uc_pensum > 0 ? ($total_uc_aprobadas / $total_uc_pensum) * 100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($materias_por_semestre as $sem => $mats): 
        $sem_label = $etiquetas_semestre[$sem] ?? "Semestre $sem";
        if ($sem === 99) $sem_label = 'Electivas / Otras';
    ?>
    <div class="card border-0 rounded-4 shadow-sm mb-3 overflow-hidden">
        <div class="card-header border-0 fw-bold py-3 px-4" style="background:#003366;color:white;">
            <i class="fas fa-layer-group me-2"></i><?= $sem_label ?>
            <span class="badge bg-light text-dark rounded-pill ms-2"><?= count($mats) ?> materias</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="small text-muted">
                    <tr>
                        <th class="ps-4 py-3">Materia</th>
                        <th>Codigo</th>
                        <th>UC</th>
                        <th>Seccion</th>
                        <th>Nota</th>
                        <th class="text-end pe-4">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mats as $m): ?>
                    <tr>
                        <td class="ps-4 py-2 fw-bold"><?= htmlspecialchars($m['nombre_materia']) ?></td>
                        <td><span class="badge bg-light text-muted border"><?= htmlspecialchars($m['codigo_materia']) ?></span></td>
                        <td><?= intval($m['uc']) ?></td>
                        <td><?= htmlspecialchars($m['nombre_seccion']) ?></td>
                        <td>
                            <?php if ($m['nota'] > 0): ?>
                                <span class="fw-bold <?= intval($m['nota']) >= 10 ? 'text-success' : 'text-danger' ?>">
                                    <?= intval($m['nota']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if ($m['estado'] === 'Aceptada' && $m['nota'] >= 10): ?>
                                <span class="badge bg-success rounded-pill px-3">Aprobada</span>
                            <?php elseif ($m['estado'] === 'Rechazada'): ?>
                                <span class="badge bg-danger rounded-pill px-3">Reprobada</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3">Pendiente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($materias)): ?>
    <div class="text-center py-5 text-muted">
        <i class="fas fa-book fa-3x mb-3 opacity-25"></i>
        <p class="fw-bold">Aun no tienes materias cursadas.</p>
        <a href="index.php?page=enrollment" class="btn btn-primary rounded-pill px-4">Ir a inscripcion</a>
    </div>
    <?php endif; ?>
</div>

