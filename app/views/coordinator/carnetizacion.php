<?php
// CARNETIZACION
if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
    header('Location: index.php?page=login');
    exit;
}

$tipo = $_GET['tipo'] ?? 'estudiante';
$es_docente = ($tipo === 'docente');
$rol_filter  = $es_docente ? 'Docente' : 'Estudiante';
$label_s     = $es_docente ? 'Docente' : 'Estudiante';
$label_p     = $es_docente ? 'Docentes' : 'Estudiantes';
$label_t     = $es_docente ? 'docente' : 'estudiante';
$icono       = $es_docente ? 'fa-chalkboard-teacher' : 'fa-user-graduate';
$tipo_other  = $es_docente ? 'estudiante' : 'docente';
$label_other = $es_docente ? 'Estudiante' : 'Docente';
$icono_other = $es_docente ? 'fa-user-graduate' : 'fa-chalkboard-teacher';

$busqueda = trim($_GET['q'] ?? '');
$estudiantes = [];
if ($busqueda !== '') {
    $like = "%{$busqueda}%";
    $stmt = $conexion->prepare(
        "SELECT u.id, u.correo, u.estado, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil, p.fecha_carnetizacion
        FROM usuario u
        JOIN perfil p ON u.id_perfil = p.id
        WHERE u.rol = :rol
        AND (p.cedula LIKE :q OR p.nombre LIKE :q OR p.segundo_nombre LIKE :q OR p.apellido LIKE :q OR p.segundo_apellido LIKE :q OR u.correo LIKE :q)
        ORDER BY p.apellido, p.nombre
        LIMIT 20"
    );
    $stmt->execute([':rol' => $rol_filter, ':q' => $like]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conexion->prepare(
        "SELECT u.id, u.correo, u.estado, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil, p.fecha_carnetizacion
        FROM usuario u
        JOIN perfil p ON u.id_perfil = p.id
        WHERE u.rol = :rol AND u.estado = 'Aprobado'
        ORDER BY p.apellido, p.nombre
        LIMIT 50"
    );
    $stmt->execute([':rol' => $rol_filter]);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$est_id   = intval($_GET['est'] ?? 0);
$est_data = null;
if ($est_id > 0) {
    $stmt = $conexion->prepare(
        "SELECT u.id, u.correo, u.estado, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil, p.fecha_carnetizacion
        FROM usuario u JOIN perfil p ON u.id_perfil = p.id
        WHERE u.id = :id AND u.rol = :rol LIMIT 1"
    );
    $stmt->execute([':id' => $est_id, ':rol' => $rol_filter]);
    $est_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
$partial = $es_docente ? 'app/views/coordinator/partials/teacher_card.php' : 'app/views/coordinator/partials/student_card.php';
$search_action = $es_docente ? 'search_teachers_ajax' : 'search_students_ajax';
$detail_action = $es_docente ? 'get_teacher_details_ajax' : 'get_student_details_ajax';
$save_action   = $es_docente ? 'save_teacher_photo' : 'save_photo';
$param_id      = $es_docente ? 'doc_id' : 'est_id';
$page_param    = 'carnetizacion' . ($es_docente ? '&tipo=docente' : '');
?>

<div class="container py-5 mt-3">

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-3 d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#003366,#005c99);width:52px;height:52px;">
                <i class="fas fa-camera text-white fa-lg"></i>
            </div>
            <div>
                <h1 class="fw-bold mb-0" style="color:#003366;font-size:1.65rem;">Carnetizacion</h1>
                <p class="text-muted mb-0 small">Captura y asigna la foto oficial del carnet universitario</p>
            </div>
        </div>
        <a href="index.php?page=dashboard" class="btn btn-sm rounded-pill px-3 fw-bold" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;transition:all .2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <div class="card border-0 rounded-4 mb-4 overflow-hidden" style="box-shadow:0 1px 3px rgba(0,0,0,0.06),0 1px 2px rgba(0,0,0,0.04);">
        <div class="p-3" style="background:#f8fafc;">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex rounded-pill overflow-hidden" style="background:#e2e8f0;padding:3px;flex-shrink:0;">
                    <a href="index.php?page=carnetizacion&tipo=estudiante<?= $busqueda ? '&q='.urlencode($busqueda) : '' ?><?= $est_id && !$es_docente ? '&est='.$est_id : '' ?>"
                    class="px-3 py-2 text-decoration-none small fw-bold rounded-pill transition-all"
                    style="background:<?= $es_docente ? 'transparent' : '#fff' ?>;color:<?= $es_docente ? '#64748b' : '#003366' ?>;box-shadow:<?= $es_docente ? 'none' : '0 1px 3px rgba(0,0,0,0.12)' ?>;">
                        <i class="fas fa-user-graduate me-1"></i>Estudiante
                    </a>
                    <a href="index.php?page=carnetizacion&tipo=docente<?= $busqueda ? '&q='.urlencode($busqueda) : '' ?><?= $est_id && $es_docente ? '&est='.$est_id : '' ?>"
                    class="px-3 py-2 text-decoration-none small fw-bold rounded-pill transition-all"
                    style="background:<?= $es_docente ? '#fff' : 'transparent' ?>;color:<?= $es_docente ? '#003366' : '#64748b' ?>;box-shadow:<?= $es_docente ? '0 1px 3px rgba(0,0,0,0.12)' : 'none' ?>;">
                        <i class="fas fa-chalkboard-teacher me-1"></i>Docente
                    </a>
                </div>
                <form method="GET" action="index.php" class="d-flex gap-2 flex-grow-1" style="min-width:260px;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="carnetizacion">
                    <input type="hidden" name="tipo" value="<?= $tipo ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-start-pill" style="border-color:#d1d9e6;">
                            <i class="fas fa-search text-muted" style="font-size:.9rem;"></i>
                        </span>
                        <input type="text" name="q" id="searchInput" value="<?= htmlspecialchars($busqueda) ?>"
                            class="form-control border-start-0 rounded-end-pill"
                            style="border-color:#d1d9e6;font-size:.95rem;padding:.45rem 1rem;outline:none;box-shadow:none;"
                            placeholder="Cedula, nombre o correo..."
                            maxlength="50"
                            autofocus oninput="this.value = this.value.replace(/[<>]/g, ''); liveSearch()">
                    </div>
                    <button type="submit" class="btn fw-bold rounded-pill px-4" style="background:#003366;color:#fff;border:none;font-size:.9rem;">
                        Buscar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-<?= $est_data ? '5' : '12' ?>" id="resultsColumn">
            <div class="card border-0 h-100" style="box-shadow:0 1px 3px rgba(0,0,0,0.06),0 1px 2px rgba(0,0,0,0.04);border-radius:14px;">
                <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0" style="color:#475569;font-size:.8rem;letter-spacing:.3px;">
                            <i class="fas fa-list me-2" style="color:#94a3b8;"></i>Resultados
                            <span class="badge rounded-pill ms-1" style="background:#e2e8f0;color:#475569;font-size:.65rem;"><?= count($estudiantes) ?></span>
                        </h6>
                    </div>
                </div>
                <div class="card-body p-3" id="resultsContainer">
                    <?php if ($busqueda === ''): ?>
                        <?php if (empty($estudiantes)): ?>
                        <div class="text-center py-5 text-muted">
                            <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width:56px;height:56px;background:#f1f5f9;">
                                <i class="fas fa-search" style="color:#94a3b8;"></i>
                            </div>
                            <p style="color:#64748b;font-size:.9rem;font-weight:500;">No hay <?= strtolower($label_p) ?> aprobados aun</p>
                            <p style="color:#94a3b8;font-size:.8rem;">Escribe cedula, nombre o correo electronico</p>
                        </div>
                        <?php else: ?>
                        <div class="d-flex flex-column gap-2" id="resultsList">
                        <?php foreach ($estudiantes as $e): ?>
                            <a href="index.php?page=carnetizacion&tipo=<?= $tipo ?>&est=<?= $e['id'] ?>"
                            class="text-decoration-none rounded-3 p-3 transition-all"
                            style="background:<?= ($est_id === intval($e['id'])) ? '#f0f7ff' : '#fff' ?>;border:1px solid <?= ($est_id === intval($e['id'])) ? '#b3d4f7' : '#eef2f6' ?>;<?= ($est_id === intval($e['id'])) ? 'border-left:3px solid #003366;' : '' ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($e['foto_perfil']): ?>
                                        <img src="public/uploads/profiles/<?= htmlspecialchars($e['foto_perfil']) ?>"
                                            class="rounded-circle" style="width:38px;height:38px;object-fit:cover;border:2px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                            style="width:38px;height:38px;background:linear-gradient(135deg,#003366,#005c99);font-size:.75rem;">
                                            <?= mb_strtoupper(mb_substr($e['nombre'],0,1).mb_substr($e['apellido'],0,1), 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold" style="color:#1e293b;font-size:.9rem;"><?= htmlspecialchars(trim("{$e['nombre']} {$e['segundo_nombre']} {$e['apellido']} {$e['segundo_apellido']}")) ?></div>
                                        <div class="small" style="color:#94a3b8;"><?= htmlspecialchars(($e['tipo_documento'] ?? 'V').'-'.$e['cedula']) ?></div>
                                    </div>
                                    <?php if ($e['foto_perfil']): ?>
                                        <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:.65rem;font-weight:600;"><i class="fas fa-check me-1"></i>Carnetizado</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:600;"><i class="fas fa-camera me-1"></i>Sin foto</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php elseif (empty($estudiantes)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i>
                            <p>No se encontraron <?= strtolower($label_p) ?> con esa busqueda.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2" id="resultsList">
                        <?php foreach ($estudiantes as $e): ?>
                            <a href="index.php?page=carnetizacion&tipo=<?= $tipo ?>&q=<?= urlencode($busqueda) ?>&est=<?= $e['id'] ?>"
                            class="text-decoration-none rounded-3 p-3 transition-all"
                            style="background:<?= ($est_id === intval($e['id'])) ? '#f0f7ff' : '#fff' ?>;border:1px solid <?= ($est_id === intval($e['id'])) ? '#b3d4f7' : '#eef2f6' ?>;<?= ($est_id === intval($e['id'])) ? 'border-left:3px solid #003366;' : '' ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($e['foto_perfil']): ?>
                                        <img src="public/uploads/profiles/<?= htmlspecialchars($e['foto_perfil']) ?>"
                                            class="rounded-circle" style="width:38px;height:38px;object-fit:cover;border:2px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                            style="width:38px;height:38px;background:linear-gradient(135deg,#003366,#005c99);font-size:.75rem;">
                                            <?= mb_strtoupper(mb_substr($e['nombre'],0,1).mb_substr($e['apellido'],0,1), 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold" style="color:#1e293b;font-size:.9rem;"><?= htmlspecialchars(trim("{$e['nombre']} {$e['segundo_nombre']} {$e['apellido']} {$e['segundo_apellido']}")) ?></div>
                                        <div class="small" style="color:#94a3b8;"><?= htmlspecialchars(($e['tipo_documento'] ?? 'V').'-'.$e['cedula']) ?></div>
                                    </div>
                                    <?php if ($e['foto_perfil']): ?>
                                        <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:.65rem;font-weight:600;"><i class="fas fa-check me-1"></i>Carnetizado</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:600;"><i class="fas fa-camera me-1"></i>Sin foto</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-<?= $est_data ? '7' : '5' ?> <?= $est_data ? '' : 'd-none' ?>" id="detailContainer" <?= $est_data ? 'style="scroll-margin-top:6rem;"' : '' ?>>
            <?php if ($est_data): ?>
                <?php include $partial; ?>
            <?php else: ?>
                <div class="card border-0 h-100 d-flex align-items-center justify-content-center" style="box-shadow:0 1px 3px rgba(0,0,0,0.06),0 1px 2px rgba(0,0,0,0.04);border-radius:14px;min-height:300px;">
                    <div class="card-body text-center py-5">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width:64px;height:64px;background:#f1f5f9;">
                            <i class="fas fa-user-circle fa-2x" style="color:#94a3b8;"></i>
                        </div>
                        <h5 style="color:#94a3b8;font-size:1rem;font-weight:600;">Selecciona un <?= strtolower($label_s) ?></h5>
                        <p style="color:#cbd5e1;font-size:.85rem;">Realiza una busqueda y elige un resultado</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.nav-pills .nav-link.active { background:#fff !important; color:#003366 !important; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
#searchInput:focus + .input-group-text, .input-group:focus-within .input-group-text { border-color:#b3d4f7 !important; }
</style>

<script>
let EST_ID  = <?= json_encode($est_id) ?>;
const CSRF    = <?= json_encode($_SESSION['csrf_token']) ?>;
const TIPO    = <?= json_encode($tipo) ?>;
const ES_DOCENTE = TIPO === 'docente';
const SEARCH_ACTION = ES_DOCENTE ? 'search_teachers_ajax' : 'search_students_ajax';
const DETAIL_ACTION = ES_DOCENTE ? 'get_teacher_details_ajax' : 'get_student_details_ajax';
const SAVE_ACTION   = ES_DOCENTE ? 'save_teacher_photo' : 'save_photo';
const PARAM_ID      = ES_DOCENTE ? 'doc_id' : 'est_id';

let stream     = null;
let photoData  = null;

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    const video = document.getElementById('video');
    if(video) video.style.display = 'none';
    const guide = document.getElementById('guide-overlay');
    if(guide) guide.style.display = 'none';
    const place = document.getElementById('cam-placeholder');
    if(place) place.style.display = 'flex';
    const activeTabId = document.querySelector('.nav-link.active').id;
    document.getElementById('btn-start').style.display = (activeTabId === 'webcam-tab') ? 'inline-block' : 'none';
}

function handleFileUpload(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            photoData = e.target.result;
            document.getElementById('upload-ui').style.display = 'none';
            const preview = document.getElementById('preview-upload');
            preview.src = photoData;
            preview.style.display = 'block';
            document.getElementById('btn-retry').style.display = 'inline-block';
            document.getElementById('btn-save').style.display = 'inline-block';
            document.getElementById('btn-start').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function startCamera() {
    const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    try {
        const constraints = { video: { width: { ideal: 640 }, height: { ideal: 640 }, facingMode: 'user' }, audio: false };
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        const video = document.getElementById('video');
        video.srcObject = stream;
        await video.play();
        document.getElementById('cam-placeholder').style.display = 'none';
        document.getElementById('canvas').style.display = 'none';
        video.style.display = 'block';
        document.getElementById('guide-overlay').style.display = 'block';
        document.getElementById('btn-start').style.display = 'none';
        document.getElementById('btn-capture').style.display = 'inline-block';
        document.getElementById('btn-retry').style.display = 'none';
        document.getElementById('btn-save').style.display = 'none';
    } catch(e) {
        let errorMsg = 'No se pudo acceder a la camara.';
        if (!isSecure) {
            errorMsg += '\n\n⚠️ DETECTADO ORIGEN NO SEGURO: Los navegadores moviles BLOQUEAN la camara si no usas HTTPS o localhost.\n\nPara probar en el telefono usa una foto con "Subir Archivo".';
        } else {
            errorMsg += '\n\nVerifica que diste permisos de camara al navegador.\n\nDetalle: ' + e.message;
        }
        notificar(errorMsg, 'error');
    }
}

function capturePhoto() {
    const video  = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 640;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    photoData = canvas.toDataURL('image/jpeg', 0.92);
    stopCamera();
    canvas.style.display = 'block';
    document.getElementById('btn-capture').style.display = 'none';
    document.getElementById('btn-retry').style.display = 'inline-block';
    document.getElementById('btn-save').style.display = 'inline-block';
}

function retryCamera() {
    photoData = null;
    document.getElementById('canvas').style.display = 'none';
    document.getElementById('preview-upload').style.display = 'none';
    document.getElementById('upload-ui').style.display = 'block';
    document.getElementById('file-input').value = '';
    document.getElementById('btn-retry').style.display = 'none';
    document.getElementById('btn-save').style.display = 'none';
    const activeTabId = document.querySelector('.nav-link.active').id;
    if (activeTabId === 'webcam-tab') {
        startCamera();
    } else {
        document.getElementById('btn-start').style.display = 'none';
    }
}

async function savePhoto() {
    if (!photoData) return;
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    try {
        const body = {};
        body[PARAM_ID] = EST_ID;
        body.photo = photoData;
        body.csrf_token = CSRF;
        const res = await fetch('index.php?action=' + SAVE_ACTION, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            const label = ES_DOCENTE ? 'docente' : 'estudiante';
            notificar(`¡Foto guardada! El carnet del ${label} ha sido actualizado.`, 'success');
            btn.style.display = 'none';
            document.getElementById('btn-retry').style.display = 'none';
        } else {
            notificar(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Foto';
        }
    } catch(e) {
        notificar('Error de red al guardar la foto: ' + e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Foto';
    }
}

async function simulate(estId, type) {
    if(!await confirmar('¿Simular vencimiento para este estudiante? Se ajustara la fecha en la BD.')) return;
    try {
        const res = await fetch('index.php?action=simulate_expiry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ est_id: estId, type: type, csrf_token: CSRF })
        });
        const data = await res.json();
        if(data.success) location.reload();
        else notificar('Error: ' + data.message, 'error');
    } catch(e) { notificar('Error: ' + e.message, 'error'); }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action="index.php"] input[name="page"][value="carnetizacion"]')?.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            liveSearch.flush && liveSearch.flush();
        });
    }

    const detailEl = document.getElementById('detailContainer');
    if (detailEl && <?= json_encode($est_id > 0) ?>) {
        setTimeout(function () {
            detailEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
});

const searchCache = new Map();
let searchTimer;
let pendingQuery = '';
let activeController = null;

function liveSearch() {
    const input = document.getElementById('searchInput');
    const query = input.value.trim();
    const container = document.getElementById('resultsContainer');
    const counter = document.querySelector('.card-header h6');

    if (query.length < 1) {
        if (activeController) { activeController.abort(); activeController = null; }
        if (container) container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-keyboard fa-2x mb-2 opacity-25"></i>
                <p class="small mb-0">Escribe para buscar resultados</p>
            </div>`;
        if (counter) counter.textContent = 'Resultados (0)';
        return;
    }

    if (searchCache.has(query)) {
        renderResults(searchCache.get(query), query);
        if (counter) counter.textContent = 'Resultados (' + searchCache.get(query).length + ')';
        return;
    }

    if (pendingQuery === query) return;
    pendingQuery = query;

    if (counter) counter.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Buscando...';
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        if (activeController) activeController.abort();
        activeController = new AbortController();

        fetch(`index.php?action=${SEARCH_ACTION}&q=${encodeURIComponent(query)}`, { signal: activeController.signal })
            .then(res => {
                if (!res.ok) throw new Error('Error HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (pendingQuery !== query) return;
                if (data.success) {
                    searchCache.set(query, data.data);
                    if (searchCache.size > 50) {
                        const firstKey = searchCache.keys().next().value;
                        searchCache.delete(firstKey);
                    }
                    renderResults(data.data, query);
                    if (counter) counter.textContent = 'Resultados (' + data.data.length + ')';
                } else {
                    if (counter) counter.textContent = 'Error en busqueda';
                }
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                if (counter) counter.textContent = 'Error de conexion';
            });
    }, 150);
}

liveSearch.flush = function () {
    clearTimeout(searchTimer);
    pendingQuery = '';
    liveSearch();
};

function renderResults(users, query) {
    const container = document.getElementById('resultsContainer');
    if (!container) return;
    const label = ES_DOCENTE ? 'docentes' : 'estudiantes';
    if (users.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i>
                <p>No se encontraron ${label} con "${query}".</p>
            </div>`;
        return;
    }
    let html = '<div class="list-group list-group-flush" id="resultsList">';
    users.forEach(e => {
        const fullName = `${e.nombre} ${e.segundo_nombre || ''} ${e.apellido} ${e.segundo_apellido || ''}`.trim();
        const activeClass = (parseInt(e.id) === EST_ID) ? 'border-primary bg-primary-subtle' : '';
        const doc = (e.tipo_documento || 'V') + '-' + e.cedula;
        let avatar = '';
        if (e.foto_perfil) {
            avatar = `<img src="public/uploads/profiles/${e.foto_perfil}" class="rounded-circle" style="width:42px;height:42px;object-fit:cover;">`;
        } else {
            const initials = (e.nombre.substring(0,1) + e.apellido.substring(0,1)).toUpperCase();
            avatar = `<div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width:42px;height:42px;background:#003366;font-size:.85rem;">${initials}</div>`;
        }
        const badge = e.foto_perfil
            ? '<span class="badge bg-success rounded-pill"><i class="fas fa-check me-1"></i>Carnetizado</span>'
            : '<span class="badge bg-warning text-dark rounded-pill"><i class="fas fa-camera me-1"></i>Sin foto</span>';
        html += `
            <a href="javascript:void(0)" onclick="selectStudent(${e.id})"
            class="list-group-item list-group-item-action rounded-3 mb-2 border ${activeClass}"
            id="sl-${e.id}"
            style="transition:all .2s;">
                <div class="d-flex align-items-center gap-3">
                    ${avatar}
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark">${fullName}</div>
                        <div class="small text-muted">${doc} · ${e.correo}</div>
                    </div>
                    ${badge}
                </div>
            </a>`;
    });
    html += '</div>';
    container.innerHTML = html;
    const counter = document.querySelector('.card-header h6');
    if (counter) counter.innerHTML = `Resultados (${users.length})`;
}

async function selectStudent(id) {
    if (stream) stopCamera();
    const container = document.getElementById('detailContainer');
    container.style.opacity = '0.5';
    container.style.pointerEvents = 'none';
    try {
        const res = await fetch(`index.php?action=${DETAIL_ACTION}&id=${id}`);
        const data = await res.json();
        if (data.success) {
            EST_ID = id;
            container.innerHTML = data.html;
            document.getElementById('resultsColumn')?.classList.replace('col-lg-12', 'col-lg-5');
            container.classList.replace('col-lg-5', 'col-lg-7');
            container.classList.remove('d-none');
            document.querySelectorAll('#resultsList .list-group-item').forEach(item => {
                item.classList.remove('border-primary', 'bg-primary-subtle');
            });
            const selected = document.getElementById('sl-' + id);
            if (selected) selected.classList.add('border-primary', 'bg-primary-subtle');
        } else {
            notificar('Error: ' + data.message, 'error');
        }
    } catch(e) {
        notificar('Error de conexion', 'error');
    } finally {
        container.style.opacity = '1';
        container.style.pointerEvents = 'all';
    }
}
</script>

