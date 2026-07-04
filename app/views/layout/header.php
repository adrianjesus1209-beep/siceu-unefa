<?php // ENCABEZADO DEL SITIO ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <title>UNEFA - Excelencia Educativa Abierta al Pueblo</title>
    <link rel="icon" type="image/png" href="<?php echo URLROOT; ?>/assets/img/logos/logo_unefa.png">
    <link href="<?php echo URLROOT; ?>/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo URLROOT; ?>/css/estilos.css?v=3" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100" style="margin:0">

    <div class="barra-superior">
        <img src="<?php echo URLROOT; ?>/assets/img/banners/cintilla.jpg" alt="Cintilla Gubernamental" class="barra-superior-cintilla">
    </div>

    <nav class="navbar navbar-expand-lg barra-navegacion-propia">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <img src="<?php echo URLROOT; ?>/assets/img/logos/logo_unefa.png" alt="Logo UNEFA" class="rounded-circle logo-header">
                <div class="d-none d-md-block">
                    <div class="fw-bold lh-1 unefa-sigla">UNEFA</div>
                    <div class="text-muted lh-1 unefa-eslogan">Excelencia Educativa</div>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none cursor-pointer" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars fs-4 icono-nav-toggler"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <ul class="navbar-nav align-items-center gap-1">
                    <li class="nav-item"><a class="enlace-navegacion nav-link" href="index.php"><i class="fas fa-home me-1"></i>Inicio</a></li>
                    <li class="nav-item"><a class="enlace-navegacion nav-link" href="https://unefazuliaingsistemas.wordpress.com/la-ingenieria-de-sistemas/contenidos-programaticos/" target="_blank"><i class="fas fa-book me-1"></i>Pensum</a></li>
                    <li class="nav-item"><a class="enlace-navegacion nav-link" href="index.php?page=prelacion"><i class="fas fa-project-diagram me-1"></i>Prelacion</a></li>
                    <li class="nav-item ms-lg-3">
                        <?php if (isset($_SESSION['id_usuario'])): ?>
                            <div class="dropdown">
                                <button class="boton-primario-personalizado btn btn-sm px-4 py-2 rounded-pill fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-2"></i><?php echo $_SESSION['nombre_usuario']; ?>
                                </button>
                                <ul class="dropdown-menu border-0 shadow-sm rounded-4 mt-2">
                                    <li><a class="dropdown-item small text-uppercase fw-bold" href="index.php?page=dashboard"><i class="fas fa-home me-2"></i>Panel</a></li>
                                    <li><a class="dropdown-item small text-uppercase fw-bold" href="index.php?page=mi_perfil"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                    <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Estudiante'): ?>
                                        <li><a class="dropdown-item small text-uppercase fw-bold" href="index.php?page=planilla" target="_blank"><i class="fas fa-file-alt me-2"></i>Ver Planilla</a></li>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador'): ?>
                                        <li><a class="dropdown-item small text-uppercase fw-bold" href="index.php?page=auditoria"><i class="fas fa-clipboard-check me-2"></i>Auditoria</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item small text-uppercase fw-bold text-danger" href="index.php?action=logout"><i class="fas fa-right-from-bracket me-2"></i>Cerrar sesion</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="index.php?page=login" class="boton-primario-personalizado btn btn-sm px-4 py-2 rounded-pill fw-bold">
                                <i class="fas fa-user-lock me-2"></i>Entrar al Sistema
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main style="flex: 1 0 auto;" class="d-flex flex-column">
