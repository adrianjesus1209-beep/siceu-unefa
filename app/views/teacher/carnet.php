<?php
// CARNET DOCENTE
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Docente') {
    header('Location: index.php?page=login');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$stmt = $conexion->prepare(
    "SELECT u.correo, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil, p.fecha_carnetizacion,
            m.nombre_materia, s.nombre_seccion
    FROM usuario u
    JOIN perfil p ON u.id_perfil = p.id
    LEFT JOIN seccion s ON s.id_docente = u.id
    LEFT JOIN materia m ON s.id_materia = m.id
    WHERE u.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id_usuario]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Error: No se pudo cargar tu perfil.</div></div>";
    exit;
}

$nombre  = strtoupper(trim("{$datos['apellido']} {$datos['segundo_apellido']}")) . ' ' . strtoupper(trim("{$datos['nombre']} {$datos['segundo_nombre']}"));
$cedula  = ($datos['tipo_documento'] ?? 'V') . '-' . $datos['cedula'];
$correo  = $datos['correo'];
$materia = strtoupper($datos['nombre_materia'] ?? 'DOCENTE');
$seccion = strtoupper($datos['nombre_seccion'] ?? '');
$vence   = 'DIC ' . (date('Y') + 1);
$qrPayload = "Nombre: {$nombre} | Cedula: {$cedula} | Correo: {$correo}";
$pdfUrl  = URLROOT . '/carnet/CARNET_UNEFA_Plantilla docente.pdf';

$error_carnet = null;
$ahora = new DateTime();
$fecha_c = $datos['fecha_carnetizacion'] ? new DateTime($datos['fecha_carnetizacion']) : null;

if (empty($datos['foto_perfil'])) {
    $error_carnet = "No disponible: Aun no has sido carnetizado. Acude a la coordinacion para la toma de tu foto.";
} elseif ($fecha_c) {
    $vencimiento = clone $fecha_c;
    $vencimiento->modify('+12 months');
    if ($ahora > $vencimiento) {
        $error_carnet = "Este carnet ha vencido (validez de 12 meses). Debes renovarlo en la coordinacion.";
    }
}

if (!empty($datos['foto_perfil'])) {
    $photoUrl = URLROOT . '/uploads/profiles/' . rawurlencode($datos['foto_perfil']);
} else {
    $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($nombre) . '&background=003366&color=fff&bold=true&size=200';
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
.carnet-page    { background:#f8fafc; min-height:calc(100vh - 80px); }
.fw-black       { font-weight:900; }
.carnet-wrapper { max-width:420px; width:100%; }
.carnet-canvas-layer {
    display:block;
    border-radius:12px;
    max-width:420px;
    width:100%;
    height:auto;
}
.carnet-overlay {
    position:absolute;
    top:0; left:0; right:0; bottom:0;
    width:100% !important;
    height:100% !important;
    border-radius:12px;
}
.carnet-loading {
    position:absolute; inset:0;
    background:#f1f5f9; border-radius:12px;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    min-height:200px;
}
@media print {
    .d-print-none   { display:none !important; }
    .carnet-wrapper { max-width:100%; }
}
</style>

<div class="container py-4 carnet-page">
    <?php if ($error_carnet): ?>
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 text-center">
                <div class="card border-0 shadow-lg rounded-4 p-5">
                    <i class="fas fa-lock fa-4x mb-4 text-warning opacity-50"></i>
                    <h3 class="fw-bold mb-3" style="color:#003366;">Carnet No Disponible</h3>
                    <p class="text-muted mb-4"><?= $error_carnet ?></p>
                    <a href="index.php?page=dashboard" class="btn btn-primary rounded-pill px-5 fw-bold" style="background:#003366;border-color:#003366;">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
    <div class="text-center mb-4 d-print-none">
        <h2 class="fw-black" style="color:#003366;">Carnet Digital Docente</h2>
        <p class="text-muted small">Basado en la plantilla oficial universitaria.</p>
    </div>
    <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
            <button onclick="downloadCarnetPDF()" class="btn btn-success rounded-pill px-4 fw-bold" id="btn-download-pdf">
                <i class="fas fa-file-pdf me-2"></i>Descargar PDF (Ambos Lados)
            </button>
            <a href="index.php?page=dashboard" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

    <div class="d-flex flex-column align-items-center gap-5 mt-4">
        <div class="carnet-wrapper shadow-lg">
            <div class="carnet-label mb-2 text-muted small fw-bold text-center">
                <i class="fas fa-credit-card me-1"></i>CARA FRONTAL
            </div>
            <div class="position-relative" id="carnet-container-front">
                <canvas id="canvas-pdf-front"  class="carnet-canvas-layer"></canvas>
                <canvas id="canvas-data-front" class="carnet-canvas-layer carnet-overlay"></canvas>
                <div id="loading-front" class="carnet-loading">
                    <div class="spinner-border" style="color:#003366;" role="status"></div>
                    <div class="mt-2 text-muted small">Cargando plantilla...</div>
                </div>
            </div>
        </div>

        <div class="carnet-wrapper shadow-lg">
            <div class="carnet-label mb-2 text-muted small fw-bold text-center">
                <i class="fas fa-id-card me-1"></i>CARA TRASERA
            </div>
            <div class="position-relative" id="carnet-container-back">
                <canvas id="canvas-pdf-back" class="carnet-canvas-layer"></canvas>
                <div id="loading-back" class="carnet-loading">
                    <div class="spinner-border" style="color:#003366;" role="status"></div>
                    <div class="mt-2 text-muted small">Cargando plantilla...</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const CARNET_DATA = {
    name:     <?= json_encode($nombre) ?>,
    idNumber: <?= json_encode($cedula) ?>,
    subject:  <?= json_encode($materia) ?>,
    section:  <?= json_encode($seccion) ?>,
    vence:    <?= json_encode($vence) ?>,
    photoUrl: <?= json_encode($photoUrl) ?>,
    qrData:   <?= json_encode($qrPayload) ?>,
    pdfUrl:   <?= json_encode($pdfUrl) ?>
};

pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const SCALE = 2.5;
let pdfDoc  = null;

async function loadAndRenderPDF() {
    try {
        pdfDoc = await pdfjsLib.getDocument(CARNET_DATA.pdfUrl).promise;
        await Promise.all([
            renderPage(1, 'canvas-pdf-front', 'loading-front', drawFrontOverlay),
            renderPage(2, 'canvas-pdf-back',  'loading-back',  null)
        ]);
    } catch (err) {
        console.error('PDF load error:', err);
        notificar('No se pudo cargar la plantilla PDF. Verifica la conexion.', 'warning');
        document.getElementById('loading-front').style.display = 'none';
        document.getElementById('loading-back').style.display = 'none';
    }
}

async function renderPage(pageNum, canvasId, loadingId, overlayCallback) {
    const page     = await pdfDoc.getPage(pageNum);
    const viewport = page.getViewport({ scale: SCALE });
    const canvas   = document.getElementById(canvasId);
    const ctx      = canvas.getContext('2d');
    canvas.width   = viewport.width;
    canvas.height  = viewport.height;
    await page.render({ canvasContext: ctx, viewport }).promise;
    document.getElementById(loadingId).style.display = 'none';
    if (overlayCallback) await overlayCallback(canvas.width, canvas.height);
}

async function drawFrontOverlay(pdfW, pdfH) {
    const overlay = document.getElementById('canvas-data-front');
    overlay.width  = pdfW;
    overlay.height = pdfH;
    const ctx = overlay.getContext('2d');

    const photoSize = pdfW * 0.505;
    const photoX    = (pdfW - photoSize) / 2;
    const photoY    = pdfH * 0.202;
    await drawRoundedPhoto(ctx, CARNET_DATA.photoUrl, photoX, photoY, photoSize, photoSize, 40 * SCALE);

    const cx = pdfW * 0.50;
    const ty = pdfH * 0.635;

    ctx.textAlign = 'center';

    ctx.font      = `bold ${26 * SCALE}px 'Arial Narrow', Arial, sans-serif`;
    ctx.fillStyle = '#0a0a0a';
    ctx.fillText(`${CARNET_DATA.idNumber}`, cx, ty);

    ctx.font      = `bold ${22 * SCALE}px 'Arial Narrow', Arial, sans-serif`;
    ctx.fillStyle = '#0a0a0a';
    ctx.fillText(CARNET_DATA.name, cx, ty + 29 * SCALE);

    ctx.font      = `bold ${15 * SCALE}px 'Arial Narrow', Arial, sans-serif`;
    ctx.fillStyle = '#111111';
    ctx.fillText(CARNET_DATA.section, cx, ty + 56 * SCALE);

    ctx.font      = `bold ${16 * SCALE}px Arial, sans-serif`;
    ctx.fillStyle = '#000000';
    ctx.fillText(CARNET_DATA.vence, pdfW * 0.485, pdfH * 0.882);

    const qrSize  = 175 * SCALE;
    const qrX     = pdfW * 0.173 - qrSize / 2;
    const qrY     = pdfH * 0.866 - qrSize / 2;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(qrX - 2, qrY - 2, qrSize + 4, qrSize + 4);

    const tmpCanvas = document.createElement('canvas');
    new QRious({ element: tmpCanvas, value: CARNET_DATA.qrData, size: qrSize, level: 'H', padding: 0 });
    ctx.drawImage(tmpCanvas, qrX, qrY, qrSize, qrSize);

    ctx.textAlign = 'left';
}

function drawRoundedPhoto(ctx, src, x, y, w, h, r) {
    return new Promise(resolve => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = () => {
            ctx.save();
            ctx.fillStyle = '#ffffff';
            roundRect(ctx, x, y, w, h, r); ctx.fill();
            ctx.beginPath();
            roundRect(ctx, x, y, w, h, r); ctx.clip();
            ctx.drawImage(img, x, y, w, h);
            ctx.restore();
            resolve();
        };
        img.onerror = () => {
            ctx.save();
            ctx.fillStyle = '#e2e8f0';
            roundRect(ctx, x, y, w, h, r); ctx.fill();
            ctx.restore();
            resolve();
        };
        img.src = src;
    });
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

async function downloadCarnetPDF() {
    const btn = document.getElementById('btn-download-pdf');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando PDF...';

    const pdfFront  = document.getElementById('canvas-pdf-front');
    const dataFront = document.getElementById('canvas-data-front');
    const merged    = document.createElement('canvas');
    merged.width    = pdfFront.width;
    merged.height   = pdfFront.height;
    const mCtx      = merged.getContext('2d');
    mCtx.drawImage(pdfFront,  0, 0);
    mCtx.drawImage(dataFront, 0, 0);

    const backCanvas = document.getElementById('canvas-pdf-back');
    const { jsPDF }  = window.jspdf;
    const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    const pageW = 297, pageH = 210, margin = 8;
    const cardW = (pageW - margin * 3) / 2;
    const cardH = pageH - margin * 2;

    pdf.addImage(merged.toDataURL('image/jpeg', 0.95),      'JPEG', margin, margin, cardW, cardH, '', 'FAST');
    pdf.addImage(backCanvas.toDataURL('image/jpeg', 0.95), 'JPEG', margin * 2 + cardW, margin, cardW, cardH, '', 'FAST');
    pdf.save('Carnet_Docente_UNEFA.pdf');

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i>Descargar PDF (Ambos Lados)';
}

document.addEventListener('DOMContentLoaded', loadAndRenderPDF);
</script>

