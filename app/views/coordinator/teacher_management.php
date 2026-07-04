<?php
// GESTION DE DOCENTES
$stmtDocentes = $conexion->prepare(
    "SELECT u.id, u.correo, u.estado, p.id as id_perfil, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil
    FROM usuario u
    JOIN perfil p ON u.id_perfil = p.id
    WHERE u.rol = 'Docente'
    ORDER BY p.apellido, p.nombre"
);
$stmtDocentes->execute();
$docentes = $stmtDocentes->fetchAll(PDO::FETCH_ASSOC);

$idsDocentes = array_column($docentes, 'id');
$materiasPorDocente = [];
if (!empty($idsDocentes)) {
    $placeholders = implode(',', array_fill(0, count($idsDocentes), '?'));
    $stmtSec = $conexion->prepare(
        "SELECT s.id as id_seccion, s.id_docente, m.id as id_materia, m.codigo_materia, m.nombre_materia, m.semestre
        FROM seccion s
        JOIN materia m ON s.id_materia = m.id
        WHERE s.id_docente IN ($placeholders)
        ORDER BY m.semestre, m.nombre_materia"
    );
    $stmtSec->execute($idsDocentes);
    foreach ($stmtSec->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $materiasPorDocente[$row['id_docente']][] = $row;
    }
}

$stmtMaterias = $conexion->query("SELECT id, codigo_materia, nombre_materia, semestre FROM materia ORDER BY CASE WHEN semestre IS NULL THEN 1 ELSE 0 END, semestre ASC, nombre_materia ASC");
$listado_materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

$stmtDocentesJson = $conexion->query("SELECT u.id, p.nombre, p.apellido, p.cedula, p.tipo_documento FROM usuario u JOIN perfil p ON u.id_perfil = p.id WHERE u.rol = 'Docente' ORDER BY p.apellido, p.nombre");
$docentes_json = json_encode($stmtDocentesJson->fetchAll(PDO::FETCH_ASSOC));
?>

<div class="container py-5 mt-5">
    <div class="row mb-5 align-items-center animate__animated animate__fadeIn">
        <div class="col-md-7">
            <h1 class="display-5 fw-bold mb-1" style="color: var(--coord-primary);"><i class="fas fa-chalkboard-teacher me-3"></i>Gestion de Docentes</h1>
            <p class="text-muted mb-0" style="font-size: 1.05rem;">Administracion de cuentas y registro de docentes.</p>
        </div>
        <div class="col-md-5 mt-3 mt-md-0 d-flex align-items-center justify-content-md-end gap-2">
            <a href="index.php?page=dashboard" class="btn btn-sm rounded-pill px-3 fw-bold d-inline-flex align-items-center gap-1" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;white-space:nowrap;transition:all .2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fas fa-arrow-left"></i>Volver
            </a>
            <button class="btn btn-sm rounded-pill px-4 fw-bold d-flex align-items-center gap-2" type="button"
                    data-bs-toggle="modal" data-bs-target="#modalRegistrarDocente"
                    style="height:44px;background:#5c6bc0;color:#fff;border:none;font-size:0.9rem;transition:all .2s;white-space:nowrap;"
                    onmouseover="this.style.background='#4a5ab9'" onmouseout="this.style.background='#5c6bc0'">
                <i class="fas fa-user-plus"></i>
                <span class="d-none d-md-inline">Registrar Docente</span>
            </button>
            <a href="index.php?page=gestion_docentes"
                class="d-flex align-items-center gap-2 rounded-pill px-3 py-2 border text-decoration-none btn-hover"
                style="background: rgba(92,107,192,0.05); border-color: rgba(92,107,192,0.15) !important; transition:all .2s;">
                <span class="fw-bold fs-5" style="color: #5c6bc0;"><?php echo count($docentes); ?></span>
                <span class="text-muted small fw-semibold" style="line-height: 1.2; font-size: 0.75rem;">Docentes<br>Registrados</span>
            </a>
            <div class="dropdown">
                <button class="btn rounded-3 d-flex align-items-center justify-content-center" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="width:44px;height:44px;background:var(--coord-primary);color:#fff;border:none;font-size:1.2rem;">
                    <i class="fas fa-bars"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-4 p-2" style="min-width:220px;">
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#f5f0ff;" href="index.php?page=gestion_docentes"><i class="fas fa-chalkboard-teacher me-2" style="color:#5c6bc0;"></i>Registrar Docente</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#fff8f0;" href="index.php?page=approve_registration"><i class="fas fa-user-check me-2" style="color:#e67e22;"></i>Aprobar registros de estudiantes</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#f5f3ff;" href="index.php?page=documentos_usuarios"><i class="fas fa-user-cog me-2" style="color:#7c3aed;"></i>Gestion de Usuarios</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#f0f7ff;" href="index.php?page=carnetizacion"><i class="fas fa-camera me-2" style="color:#1976d2;"></i>Carnetizacion</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#f0fdfa;" href="index.php?page=gestion_carnets"><i class="fas fa-sync-alt me-2" style="color:#0891b2;"></i>Gestion de Carnets</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#fff7ed;" href="index.php?page=gestion_periodos"><i class="fas fa-calendar-alt me-2" style="color:#c2410c;"></i>Periodos academicos</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#f0fdf4;" href="index.php?page=cronograma"><i class="fas fa-calendar-week me-2" style="color:#16a34a;"></i>Cronograma academico</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#f8f9fa;" href="index.php?page=mi_perfil"><i class="fas fa-user me-2" style="color:#6b7280;"></i>Mi Perfil</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 overflow-hidden" style="box-shadow:0 1px 3px rgba(0,0,0,0.06);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-uppercase" style="color:#64748b;background:#f8fafc;">
                            <th class="ps-4 py-3">DOCENTE</th>
                            <th>CEDULA</th>
                            <th>CORREO</th>
                            <th>ESTADO</th>
                            <th>MATERIAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($docentes)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-chalkboard-teacher fa-3x mb-3 opacity-25"></i>
                                <p>No hay docentes registrados.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($docentes as $d): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width:36px;height:36px;background:linear-gradient(135deg,#5c6bc0,#7986cb);font-size:.7rem;flex-shrink:0;">
                                        <?php echo mb_strtoupper(mb_substr($d['nombre'],0,1) . mb_substr($d['apellido'],0,1), 'UTF-8'); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="color:#1e293b;font-size:.85rem;"><?php echo htmlspecialchars(trim($d['nombre'] . ' ' . $d['segundo_nombre'] . ' ' . $d['apellido'] . ' ' . $d['segundo_apellido'])); ?></div>
                                        <div class="small" style="color:#94a3b8;"><?php echo htmlspecialchars($d['correo']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-muted border fw-normal"><?php echo htmlspecialchars(($d['tipo_documento'] ?? 'V') . '-' . $d['cedula']); ?></span></td>
                            <td class="small text-muted"><?php echo htmlspecialchars($d['correo']); ?></td>
                            <td>
                                <span class="badge rounded-pill fw-semibold" style="background:<?php echo $d['estado'] == 'Aprobado' ? '#dcfce7' : ($d['estado'] == 'Pendiente' ? '#fef9c3' : '#fee2e2'); ?>;color:<?php echo $d['estado'] == 'Aprobado' ? '#166534' : ($d['estado'] == 'Pendiente' ? '#854d0e' : '#991b1b'); ?>;">
                                    <?php echo $d['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-1" style="min-width:160px;max-width:280px;">
                                    <?php
                                    $secs = $materiasPorDocente[$d['id']] ?? [];
                                    $total_materias = count($secs);
                                    foreach ($secs as $i => $s):
                                    ?>
                                        <span class="badge border fw-normal small d-inline-flex align-items-center gap-1" style="background:#f0f7ff;color:#003366;border-color:#cce0ff !important;font-size:.7rem;">
                                            <?php echo htmlspecialchars($s['codigo_materia']); ?>
                                            <button type="button" class="btn-remove-subject"
                                                data-id="<?php echo $s['id_seccion']; ?>"
                                                data-id-materia="<?php echo $s['id_materia']; ?>"
                                                data-materia="<?php echo htmlspecialchars($s['codigo_materia'] . ' - ' . $s['nombre_materia']); ?>"
                                                data-docente="<?php echo htmlspecialchars($d['nombre'] . ' ' . $d['apellido']); ?>"
                                                data-docente-id="<?php echo $d['id']; ?>"
                                                data-total="<?php echo $total_materias; ?>"
                                                title="Reasignar materia"
                                                style="background:none;border:none;color:#dc3545;padding:0;font-size:.65rem;line-height:1;cursor:pointer;">&times;</button>
                                        </span>
                                    <?php endforeach; ?>
                                    <button class="btn btn-sm rounded-pill fw-bold assign-btn"
                                        data-id="<?php echo $d['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($d['nombre'].' '.$d['apellido']); ?>"
                                        style="background:transparent;color:#5c6bc0;border:1px dashed #5c6bc0;font-size:.7rem;padding:.15rem .55rem;"
                                        title="Asignar materia">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
body:has(.reasignar-materia-modal.show) .modal-backdrop {
    backdrop-filter: blur(6px) !important;
    -webkit-backdrop-filter: blur(6px) !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}
</style>

<div class="modal fade" id="modalAsignarMateria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #5c6bc0, #7986cb);">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-3" style="background:rgba(255,255,255,0.2);">
                        <i class="fas fa-book text-white fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold m-0 text-white">Asignar Materia a Docente</h5>
                        <small class="text-white opacity-75" id="asignar-docente-nombre"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background:#f5f8ff;">
                <form id="formAsignarMateria">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id_docente" id="asignar-id-docente">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold" style="color:#003366;">Materia</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-color:#d1d9e6;"><i class="fas fa-book" style="color:#5c6bc0;"></i></span>
                                <select name="id_materia" class="form-control border-start-0" required style="border-color:#d1d9e6;">
                                    <option value="">-- Seleccione una materia --</option>
                                    <?php foreach ($listado_materias as $matItem):
                                        $sem = $matItem['semestre'];
                                        if ($sem === null) {
                                            $lbl = "Electiva: " . htmlspecialchars($matItem['nombre_materia']) . " (" . htmlspecialchars($matItem['codigo_materia']) . ")";
                                        } elseif ($sem == 0) {
                                            $lbl = "CINU: " . htmlspecialchars($matItem['nombre_materia']) . " (" . htmlspecialchars($matItem['codigo_materia']) . ")";
                                        } else {
                                            $lbl = "Semestre " . htmlspecialchars($sem) . ": " . htmlspecialchars($matItem['nombre_materia']) . " (" . htmlspecialchars($matItem['codigo_materia']) . ")";
                                        }
                                    ?>
                                        <option value="<?php echo $matItem['id']; ?>"><?php echo $lbl; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold" style="color:#003366;">Seccion</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-color:#d1d9e6;"><i class="fas fa-users" style="color:#5c6bc0;"></i></span>
                                <select name="seccion_num" class="form-control border-start-0" required style="border-color:#d1d9e6;">
                                    <option value="1">Seccion D1 (0Ns-2629-D1)</option>
                                    <option value="2">Seccion D2 (0N-2629-D2)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn rounded-pill px-4 fw-bold text-white" id="btn-asignar-materia" style="background:#5c6bc0;border:none;">
                            <i class="fas fa-plus-circle me-2"></i>Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade registrar-docente-modal" id="modalRegistrarDocente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #003366, #00509e);">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-3" style="background:rgba(255,255,255,0.2);">
                        <i class="fas fa-chalkboard-teacher text-white fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold m-0 text-white">Registrar Nuevo Docente</h5>
                        <small class="text-white opacity-75">La cuenta se crearA como Aprobada inmediatamente.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background:#f5f8ff;">
                <form action="index.php?action=register_teacher" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="row g-3">
                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Primer Nombre</label>
                            <div class="reg-input-group">
                                <i class="fas fa-user reg-icon"></i>
                                <input type="text" name="nombre" class="reg-input" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Segundo Nombre</label>
                            <div class="reg-input-group">
                                <i class="fas fa-user reg-icon"></i>
                                <input type="text" name="segundo_nombre" class="reg-input">
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Primer Apellido</label>
                            <div class="reg-input-group">
                                <i class="fas fa-user-tag reg-icon"></i>
                                <input type="text" name="apellido" class="reg-input" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Segundo Apellido</label>
                            <div class="reg-input-group">
                                <i class="fas fa-user-tag reg-icon"></i>
                                <input type="text" name="segundo_apellido" class="reg-input">
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Documento de Identidad</label>
                            <div class="d-flex w-100">
                                <select name="tipo_documento" class="reg-input border-end-0" style="min-width:65px; max-width:20vw; flex-shrink: 0; border-radius: 12px 0 0 12px; padding: 0.85rem 0.5rem 0.85rem 1rem; font-weight: bold; cursor: pointer;">
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                </select>
                                <div class="reg-input-group flex-grow-1">
                                    <i class="fas fa-id-card reg-icon"></i>
                                    <input type="text" name="cedula" class="reg-input" required style="border-radius: 0 12px 12px 0;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Correo Institucional</label>
                            <div class="reg-input-group">
                                <i class="fas fa-envelope reg-icon"></i>
                                <input type="email" name="correo" class="reg-input" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Telefono</label>
                            <div class="reg-input-group">
                                <i class="fas fa-phone reg-icon"></i>
                                <input type="tel" name="telefono" class="reg-input">
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Clave Temporal</label>
                            <div class="reg-input-group position-relative">
                                <i class="fas fa-lock reg-icon"></i>
                                <input type="password" name="clave" id="clave-docente" class="reg-input pe-5" required minlength="8">
                                <button type="button" class="reg-eye-btn" onclick="toggleDocPass(this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm position-absolute" onclick="generarClaveDocente()" title="Generar clave aleatoria" tabindex="-1" style="right:2.6rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#003f80;cursor:pointer;z-index:5;">
                                    <i class="fas fa-dice"></i>
                                </button>
                            </div>
                            <div class="form-text small mt-1" style="color:#0369a1;"><i class="fas fa-info-circle me-1"></i>Use el boton <i class="fas fa-dice"></i> para generar una clave automatica. Entreguela personalmente al docente.</div>
                        </div>

                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Materia Asignada</label>
                            <div class="reg-input-group">
                                <i class="fas fa-book reg-icon"></i>
                                <select name="id_materia" class="reg-input" required style="padding-left: 2.8rem;">
                                    <option value="">-- Eliga un Curso --</option>
                                    <?php foreach ($listado_materias as $matItem): 
                                        $sem = $matItem['semestre'];
                                        if ($sem === null) {
                                            $lbl = "Electiva: " . htmlspecialchars($matItem['nombre_materia']) . " (" . htmlspecialchars($matItem['codigo_materia']) . ")";
                                        } elseif ($sem == 0) {
                                            $lbl = "CINU: " . htmlspecialchars($matItem['nombre_materia']) . " (" . htmlspecialchars($matItem['codigo_materia']) . ")";
                                        } else {
                                            $lbl = "Semestre " . htmlspecialchars($sem) . ": " . htmlspecialchars($matItem['nombre_materia']) . " (" . htmlspecialchars($matItem['codigo_materia']) . ")";
                                        }
                                    ?>
                                        <option value="<?php echo $matItem['id']; ?>">
                                            <?php echo $lbl; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small fw-bold" style="color:#003366;">Seccion</label>
                            <div class="reg-input-group">
                                <i class="fas fa-users reg-icon"></i>
                                <select name="seccion_num" class="reg-input" required style="padding-left: 2.8rem;">
                                    <option value="1">Seccion D1 (0Ns-2629-D1)</option>
                                    <option value="2">Seccion D2 (0N-2629-D2)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn fw-bold py-3 rounded-pill text-white" style="background:linear-gradient(135deg,#003366,#00509e);border:none;font-size:1rem;letter-spacing:0.5px;transition:all 0.3s;">
                            <i class="fas fa-user-plus me-2"></i>Crear Cuenta Docente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade reasignar-materia-modal" id="modalReasignarMateria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #e65100, #ff8f00);">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-3" style="background:rgba(255,255,255,0.2);">
                        <i class="fas fa-exchange-alt text-white fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold m-0 text-white">Reasignar Materia</h5>
                        <span class="badge mt-1 fw-semibold" id="reasignar-info-materia" style="background:rgba(255,255,255,0.25);color:#fff;font-size:.8rem;"></span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background:#fff8f0;">
                <div class="alert alert-info rounded-3 border-0 py-2 mb-4 d-flex align-items-center gap-2" style="background:#fff3e0;color:#bf360c;">
                    <i class="fas fa-info-circle"></i>
                    <small class="fw-semibold" id="reasignar-actual-docente"></small>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 rounded-4 h-100" style="box-shadow:0 2px 8px rgba(0,0,0,0.06);background:#fafcff;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#e8f5e9;">
                                        <i class="fas fa-user-check" style="color:#2e7d32;"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0" style="color:#1b5e20;">Docente Existente</h6>
                                        <small class="text-muted">Reasignar a un docente ya registrado</small>
                                    </div>
                                </div>
                                <form id="formReasignarExistente">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="id_seccion" id="reasignar-id-seccion">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold" style="color:#003366;">Seleccionar Docente</label>
                                        <select name="id_nuevo_docente" id="reasignar-select-docente" class="form-control" required style="border-color:#d1d9e6;">
                                            <option value="">-- Seleccione --</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn w-100 rounded-pill fw-bold text-white" id="btn-reasignar-existente" style="background:#2e7d32;border:none;padding:0.6rem;">
                                        <i class="fas fa-exchange-alt me-2"></i>Reasignar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 rounded-4 h-100" style="box-shadow:0 2px 8px rgba(0,0,0,0.06);background:#fafcff;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#e3f2fd;">
                                        <i class="fas fa-user-plus" style="color:#1565c0;"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0" style="color:#003366;">Nuevo Docente</h6>
                                        <small class="text-muted">Registrar un nuevo docente</small>
                                    </div>
                                </div>
                                <form action="index.php?action=register_teacher" method="POST" id="formReasignarNuevo">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="id_seccion_reasignar" id="reasignar-id-seccion-nuevo">
                                    <input type="hidden" name="id_materia" id="reasignar-id-materia-nuevo">
                                    <input type="hidden" name="seccion_num" value="1">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold" style="color:#003366;">Nombre</label>
                                            <input type="text" name="nombre" class="form-control form-control-sm" required style="border-color:#d1d9e6;">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold" style="color:#003366;">Apellido</label>
                                            <input type="text" name="apellido" class="form-control form-control-sm" required style="border-color:#d1d9e6;">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold" style="color:#003366;">Cedula</label>
                                            <div class="input-group input-group-sm">
                                                <select name="tipo_documento" class="form-control" style="max-width:50px;border-color:#d1d9e6;padding:0.25rem;">
                                                    <option value="V">V</option>
                                                    <option value="E">E</option>
                                                </select>
                                                <input type="text" name="cedula" class="form-control" required style="border-color:#d1d9e6;">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold" style="color:#003366;">Correo</label>
                                            <input type="email" name="correo" class="form-control form-control-sm" required style="border-color:#d1d9e6;">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold" style="color:#003366;">Telefono</label>
                                            <input type="text" name="telefono" class="form-control form-control-sm" style="border-color:#d1d9e6;">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold" style="color:#003366;">Clave</label>
                                            <input type="password" name="clave" class="form-control form-control-sm" required minlength="8" style="border-color:#d1d9e6;">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn w-100 rounded-pill fw-bold text-white mt-3" style="background:#1565c0;border:none;padding:0.6rem;">
                                        <i class="fas fa-user-plus me-2"></i>Crear y Reasignar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 pt-2 border-top" style="border-color:#e0d6c8 !important;">
                    <button type="button" class="btn btn-light rounded-pill px-5" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const docentesDisponibles = <?php echo $docentes_json; ?>;

function toggleDocPass(btn) {
    const input = document.getElementById('clave-docente');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
function generarClaveDocente() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@#$&';
    let pass = '';
    for (let i = 0; i < 12; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const input = document.getElementById('clave-docente');
    input.value = pass;
    input.type = 'password';
    input.dispatchEvent(new Event('input'));
}

document.querySelectorAll('.btn-remove-subject').forEach(btn => {
    btn.addEventListener('click', function() {
        const id_seccion = this.dataset.id;
        const id_materia = this.dataset.idMateria;
        const materia = this.dataset.materia;
        const docente = this.dataset.docente;
        const docente_id = parseInt(this.dataset.docenteId);
        const total = parseInt(this.dataset.total);

        if (total <= 1) {
            notificar('No se puede reasignar la unica materia del docente. Asignale otra primero.', 'warning');
            return;
        }

        document.getElementById('reasignar-info-materia').textContent = materia;
        document.getElementById('reasignar-actual-docente').innerHTML = '<i class="fas fa-chalkboard-teacher me-2"></i>Actual: <strong>' + docente + '</strong>';
        document.getElementById('reasignar-id-seccion').value = id_seccion;
        document.getElementById('reasignar-id-seccion-nuevo').value = id_seccion;
        document.getElementById('reasignar-id-materia-nuevo').value = id_materia;

        const select = document.getElementById('reasignar-select-docente');
        select.innerHTML = '<option value="">-- Seleccione --</option>';
        docentesDisponibles.forEach(d => {
            if (d.id !== docente_id) {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.nombre + ' ' + d.apellido + ' (' + d.tipo_documento + '-' + d.cedula + ')';
                select.appendChild(opt);
            }
        });
        select.value = '';

        document.getElementById('formReasignarNuevo').reset();

        const modal = new bootstrap.Modal(document.getElementById('modalReasignarMateria'));
        modal.show();
    });
});

document.getElementById('formReasignarExistente').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-reasignar-existente');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Reasignando...';
    const formData = new FormData(this);
    try {
        const res = await fetch('index.php?action=reassign_teacher_subject', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalReasignarMateria'));
            if (modal) modal.hide();
            notificar(data.message || 'Materia reasignada correctamente.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            notificar(data.message || 'Error al reasignar.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Reasignar';
        }
    } catch(e) {
        notificar('Error: ' + e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Reasignar';
    }
});

document.querySelectorAll('.assign-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('asignar-id-docente').value = this.dataset.id;
        document.getElementById('asignar-docente-nombre').textContent = this.dataset.nombre;
        document.getElementById('formAsignarMateria').reset();
        const modal = new bootstrap.Modal(document.getElementById('modalAsignarMateria'));
        modal.show();
    });
});

document.getElementById('formAsignarMateria').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-asignar-materia');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Asignando...';
    const formData = new FormData(this);
    try {
        const res = await fetch('index.php?action=assign_teacher_subject', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            notificar('Materia asignada correctamente.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            notificar(data.message || 'Error al asignar.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus-circle me-2"></i>Asignar';
        }
    } catch(e) {
        notificar('Error: ' + e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus-circle me-2"></i>Asignar';
    }
});
</script>

