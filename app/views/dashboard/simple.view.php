<?php
// PANEL DE COORDINADOR
$periodoVencido = $conexion->query("SELECT nombre FROM periodo_academico WHERE estado = 'Activo' AND fecha_fin IS NOT NULL AND fecha_fin < CURDATE() LIMIT 1")->fetchColumn();

$user_role = $_SESSION['rol_usuario'] ?? 'Estudiante';
$theme_color = '#003366';
$theme_bg = 'rgba(0, 51, 102, 0.05)';
$theme_border = '#003366';
$role_badge = 'Tablero General';

$stmtFoto = $conexion->prepare("SELECT p.foto_perfil FROM usuario u JOIN perfil p ON u.id_perfil = p.id WHERE u.id = :id");
$stmtFoto->execute([':id' => $_SESSION['id_usuario']]);
$foto_perfil = $stmtFoto->fetchColumn();
$tieneFoto = !empty($foto_perfil) && $foto_perfil !== 'default.svg' && file_exists('public/uploads/profiles/' . $foto_perfil);
$urlFoto = $tieneFoto ? URLROOT . '/uploads/profiles/' . rawurlencode($foto_perfil) : '';

if ($user_role === 'Docente') {
    $theme_color = '#7b1fa2';
    $theme_bg = 'rgba(123, 31, 162, 0.05)';
    $theme_border = '#7b1fa2';
    $role_badge = 'Portal Docente';
} else if ($user_role === 'Estudiante') {
    $theme_color = '#0d9488';
    $theme_bg = 'rgba(13, 148, 136, 0.05)';
    $theme_border = '#0d9488';
    $role_badge = 'Portal Estudiante';
} else if ($user_role === 'Coordinador') {
    $role_badge = 'Coordinacion academica';
}

$stmtDocentes = $conexion->prepare(
    "SELECT u.id, u.correo, u.estado, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil
    FROM usuario u
    JOIN perfil p ON u.id_perfil = p.id
    WHERE u.rol = 'Docente'
    ORDER BY p.apellido, p.nombre"
);
$stmtDocentes->execute();
$docentes = $stmtDocentes->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4 mt-3">
    <div class="py-3 px-4 mb-3 rounded-4 border-0 d-flex flex-column flex-md-row align-items-center gap-2 text-center text-md-start welcome-banner" 
        style="border-left: 6px solid <?php echo $theme_color; ?> !important; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);">
        <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center flex-shrink-0 border shadow-sm" 
            style="width: 81px; height: 81px; border: 3px solid <?php echo $theme_color; ?> !important; background: <?php echo $theme_bg; ?>;">
            <?php if ($tieneFoto): ?>
                <img src="<?php echo $urlFoto; ?>" alt="Foto de perfil"
                    style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <i class="fas fa-user" style="font-size:2.25rem;color:<?php echo $theme_color; ?>;opacity:0.4;"></i>
            <?php endif; ?>
        </div>
        <div class="flex-grow-1">
            <div class="badge px-3 py-2 rounded-pill mb-2 text-uppercase text-white font-weight-bold" 
                style="background: <?php echo $theme_color; ?> !important; letter-spacing: 1px; font-size: 0.65rem;">
                <?php echo $role_badge; ?>
            </div>
            <h1 class="fw-bold mb-1 text-dark" style="font-family: 'Outfit', sans-serif;font-size:2.25rem;">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></h1>
            <p class="text-muted mb-0" style="font-size: 0.95rem;">Has iniciado sesion correctamente como <strong style="color: <?php echo $theme_color; ?>;"><?php echo htmlspecialchars($_SESSION['rol_usuario']); ?></strong>.</p>
        </div>
        <?php if ($_SESSION['rol_usuario'] === 'Coordinador'): ?>
        <div class="flex-shrink-0" style="position:relative;z-index:10700;">
            <button class="btn rounded-3 d-flex align-items-center justify-content-center" type="button"
                    onclick="toggleHamburgerMenu(this)"
                    style="width:44px;height:44px;background:<?php echo $theme_color; ?>;color:#fff;border:none;font-size:1.2rem;">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <?php endif; ?>
        <a href="index.php?page=mi_perfil" class="btn rounded-pill px-4 fw-bold shadow-sm" style="background:<?php echo $theme_color; ?>;color:#fff;border:none;">
            <i class="fas fa-user me-2"></i>Mi Perfil
        </a>
    </div>

    <div id="hamburgerOverlay" class="position-fixed start-0 top-0 w-100 h-100" style="z-index:10001;display:none;background:transparent;" onclick="closeHamburgerMenu()"></div>
    <div id="hamburgerPanel" class="position-fixed border-0 shadow-lg rounded-4 p-2 bg-white" style="z-index:10002;display:none;min-width:240px;">
        <ul class="list-unstyled mb-0">
            <li><a class="dropdown-item rounded-3 py-2 px-3 fw-bold small" style="background:#f5f0ff;" href="index.php?page=gestion_docentes" onclick="closeHamburgerMenu()"><i class="fas fa-chalkboard-teacher me-2" style="color:#5c6bc0;"></i>Registrar Docente</a></li>
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
    <script>
    function toggleHamburgerMenu(btn) {
        const panel = document.getElementById('hamburgerPanel');
        const overlay = document.getElementById('hamburgerOverlay');
        if (panel.style.display === 'block') {
            closeHamburgerMenu();
            return;
        }
        const rect = btn.getBoundingClientRect();
        panel.style.top = (rect.bottom + 8) + 'px';
        panel.style.right = (window.innerWidth - rect.right) + 'px';
        panel.style.display = 'block';
        overlay.style.display = 'block';
    }
    function closeHamburgerMenu() {
        document.getElementById('hamburgerPanel').style.display = 'none';
        document.getElementById('hamburgerOverlay').style.display = 'none';
    }
    </script>

    <?php if ($periodoVencido && $_SESSION['rol_usuario'] === 'Coordinador'): ?>
    <div class="alert alert-warning alert-dismissible fade show rounded-4 shadow-sm mb-4 border-0 d-flex align-items-center gap-3" role="alert" style="background:#fffbeb;color:#92400e;border-left:5px solid #f59e0b!important;">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <div class="flex-grow-1">
            <strong>Periodo vencido:</strong> «<?= htmlspecialchars($periodoVencido) ?>» tiene fecha de fin anterior a hoy.
            <a href="index.php?page=gestion_periodos" class="alert-link fw-bold">Ir a Periodos academicos</a> para finalizarlo.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($_SESSION['rol_usuario'] === 'Coordinador'): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway coord-purple-border" style="cursor:pointer;" onclick="window.location.href='index.php?page=gestion_docentes'">
                    <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-gateway text-purple" style="background: rgba(92, 107, 192, 0.1); color:#5c6bc0;">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Registrar Docente</h5>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                        <p class="text-muted small">Crea cuentas de acceso para nuevos docentes de forma rapida y segura.</p>
                        <span class="btn btn-outline-card-purple rounded-pill px-4 w-100 fw-bold action-btn">Registrar <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=approve_registration" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway coord-border">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway text-warning" style="background: rgba(230, 126, 34, 0.1);">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Aprobar registros de estudiantes</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Revisa las solicitudes de inscripcion de nuevos estudiantes y sus documentos oficiales.</p>
                            <span class="btn btn-outline-card-warning rounded-pill px-4 w-100 fw-bold action-btn">Ir al Panel <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=documentos_usuarios" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway coord-user-border">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway text-purple" style="background: rgba(124, 58, 237, 0.1); color:#7c3aed;">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Gestion de Usuarios</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Activar o desactivar cuentas de estudiantes y docentes del sistema.</p>
                            <span class="btn btn-outline-card-user rounded-pill px-4 w-100 fw-bold action-btn">Gestionar Usuarios <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=carnetizacion" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway coord-blue-border">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway text-primary" style="background: rgba(0, 51, 102, 0.1);">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Carnetizacion</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Captura y asigna la foto oficial del carnet universitario a cada estudiante mediante webcam.</p>
                            <span class="btn btn-outline-card-blue rounded-pill px-4 w-100 fw-bold action-btn">Ir a Carnetizacion <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=gestion_carnets" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway coord-cyan-border">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway text-cyan" style="background: rgba(8, 145, 178, 0.1); color:#0891b2;">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Gestion de Carnets</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Listado completo de usuarios con estado de carnet y simulacion de vencimientos.</p>
                            <span class="btn btn-outline-card-cyan rounded-pill px-4 w-100 fw-bold action-btn">Ver listado <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=gestion_periodos" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway coord-orange-border">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-gateway text-orange" style="background: rgba(194, 65, 12, 0.1); color:#c2410c;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Periodos academicos</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Gestione los periodos academicos, reviselos y decida cuando iniciarlos o finalizarlos.</p>
                            <span class="btn btn-outline-card-orange rounded-pill px-4 w-100 fw-bold action-btn">Gestionar Periodos <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=cronograma" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway" style="border-left: 4px solid #16a34a;">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-gateway" style="background: rgba(22, 163, 74, 0.1); color:#16a34a;">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Cronograma academico</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Calendario de actividades academicas por ano: CINU, Pregrado, Extension, Postgrado y PIV.</p>
                            <span class="btn rounded-pill px-4 w-100 fw-bold action-btn" style="color:#16a34a;border:1px solid #16a34a;background:transparent;">Ver Cronograma <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>

        <?php endif; ?>

        <?php if ($_SESSION['rol_usuario'] === 'Docente'): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=dashboard" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway doc-purple-border">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway text-purple" style="background: rgba(123, 31, 162, 0.1); color: #7b1fa2;">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Panel de Docente</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Accede a tus secciones asignadas, lista de alumnos en curso y carga de notas finales.</p>
                            <span class="btn btn-outline-purple rounded-pill px-4 w-100 fw-bold action-btn">Ir al Panel <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=cronograma" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway" style="border-left: 4px solid #16a34a;">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway" style="background: rgba(22, 163, 74, 0.1); color:#16a34a;">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Cronograma academico</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Calendario de actividades academicas por categoria: CINU, Pregrado, Extension, Postgrado y PIV.</p>
                            <span class="btn rounded-pill px-4 w-100 fw-bold action-btn" style="color:#16a34a;border:1px solid #16a34a;background:transparent;">Ver Cronograma <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['rol_usuario'] === 'Estudiante'): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=dashboard" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway est-teal-border">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway text-teal" style="background: rgba(13, 148, 136, 0.1); color: #0d9488;">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Panel de Estudiante</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Consulta tus materias cursadas, carnet digital y record academico historico.</p>
                            <span class="btn btn-outline-teal rounded-pill px-4 w-100 fw-bold action-btn">Ir al Panel <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="index.php?page=cronograma" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-gateway" style="border-left: 4px solid #16a34a;">
                        <div class="card-tag border-bottom-0 pt-4 px-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-gateway" style="background: rgba(22, 163, 74, 0.1); color:#16a34a;">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">Cronograma academico</h5>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2 d-flex flex-column justify-content-between" style="min-height: 150px;">
                            <p class="text-muted small">Calendario de actividades academicas por categoria: CINU, Pregrado, Extension, Postgrado y PIV.</p>
                            <span class="btn rounded-pill px-4 w-100 fw-bold action-btn" style="color:#16a34a;border:1px solid #16a34a;background:transparent;">Ver Cronograma <i class="fas fa-chevron-right ms-2 btn-arrow"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>




