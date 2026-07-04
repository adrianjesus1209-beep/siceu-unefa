<?php
// CODIGO QR
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Estudiante') {
    header('Location: index.php?page=login');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$base_datos = new Database();
$conexion = $base_datos->getConnection();

$stmt = $conexion->prepare(
    "SELECT u.correo, p.nombre, p.apellido, p.cedula
    FROM usuario u
    JOIN perfil p ON u.id_perfil = p.id
    WHERE u.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id_usuario]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Error: No se pudo cargar tu perfil.</div></div>";
    exit;
}

$stmtSec = $conexion->prepare(
    "SELECT 1 FROM solicitud_inscripcion WHERE id_estudiante = :id AND estado = 'Aceptada' LIMIT 1"
);
$stmtSec->execute([':id' => $id_usuario]);
$has_enrollment = (bool)$stmtSec->fetch();

if (!$has_enrollment) {
    echo "<div class='container py-5 mt-5'>
            <div class='row justify-content-center'>
                <div class='col-md-6 text-center'>
                    <div class='card border-0 shadow-lg rounded-4 p-5'>
                        <i class='fas fa-user-clock fa-4x mb-4 text-warning opacity-50'></i>
                        <h3 class='fw-bold mb-3' style='color:#003366;'>Inscripcion Requerida</h3>
                        <p class='text-muted mb-4'>No puedes acceder a tu codigo QR hasta que estes inscrito en al menos una seccion aprobada. Por favor, solicita tu inscripcion y espera la aprobacion del docente.</p>
                        <a href='index.php?page=dashboard' class='btn btn-primary rounded-pill px-5 fw-bold' style='background:#003366;border-color:#003366;'>
                            <i class='fas fa-arrow-left me-2'></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>";
    exit;
}

$nombre    = htmlspecialchars($datos['nombre'] . ' ' . $datos['apellido']);
$cedula    = htmlspecialchars($datos['cedula']);
$correo    = htmlspecialchars($datos['correo']);

$qrPayload = "Nombre: {$nombre} | Cedula: {$cedula} | Correo: {$correo}";
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

<div class="container py-5 mt-3">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header text-white text-center py-4" style="background: #003366;">
                    <i class="fas fa-qrcode fs-1 mb-2 d-block"></i>
                    <h4 class="mb-0 fw-bold">Codigo QR Estudiantil</h4>
                    <p class="mb-0 mt-1 small opacity-75">Tu identificacion digital universitaria</p>
                </div>

                <div class="card-body text-center p-4">
                    <div class="d-flex justify-content-center my-3 mx-auto p-3 bg-white rounded-3 shadow-sm"
                        style="width:240px; height:240px; border:1px solid #e2e8f0;">
                        <div id="qrcode"></div>
                    </div>

                    <div class="mt-4 p-3 rounded-4 text-start" style="background:#f1f5f9; border:1px solid #e2e8f0;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:28px;height:28px;background:#003366;">
                                <i class="fas fa-user-graduate small"></i>
                            </span>
                            <strong class="text-dark">Datos del Estudiante</strong>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-4 text-muted">Nombre:</div>
                            <div class="col-8 fw-bold"><?= htmlspecialchars($nombre) ?></div>
                            <div class="col-4 text-muted">Cedula:</div>
                            <div class="col-8 fw-bold"><?= htmlspecialchars($cedula) ?></div>
                            <div class="col-4 text-muted">Correo:</div>
                            <div class="col-8 fw-bold text-truncate"><?= htmlspecialchars($correo) ?></div>
                        </div>
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <button class="btn btn-primary rounded-pill py-2 fw-bold" onclick="downloadQR()"
                                style="background:#003366; border-color:#003366;">
                            <i class="fas fa-download me-2"></i>Descargar QR
                        </button>
                        <a href="index.php?page=mi_carnet" class="btn btn-outline-success rounded-pill py-2 fw-bold">
                            <i class="fas fa-id-card me-2"></i>Ver Carnet Digital
                        </a>
                        <a href="index.php?page=dashboard" class="btn btn-outline-secondary rounded-pill py-2">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const qrContainer = document.getElementById('qrcode');
    const qrContent   = <?= json_encode($qrPayload) ?>;

    try {
        const canvas = document.createElement('canvas');
        canvas.style.maxWidth  = '200px';
        canvas.style.width     = '100%';
        canvas.style.height    = 'auto';
        qrContainer.appendChild(canvas);

        new QRious({
            element: canvas,
            value:   qrContent,
            size:    300,
            level:   'H'
        });
    } catch (e) {
        notificar('Error al generar el QR: ' + e.message, 'error');
    }
});

function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    if (canvas) {
        const link = document.createElement('a');
        link.href     = canvas.toDataURL('image/png');
        link.download = 'QR_Identificacion_UNEFA.png';
        link.click();
    } else {
        notificar('No se pudo generar la imagen para descargar.', 'error');
    }
}
</script>

