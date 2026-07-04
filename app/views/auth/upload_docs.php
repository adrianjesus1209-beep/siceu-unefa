<?php
// SUBIDA DE DOCUMENTOS
$id_usuario = $_SESSION['id_usuario_temp'] ?? $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    header('Location: index.php?page=login');
    exit;
}

$db = new Database();
$conexion = $db->getConnection();
$stmt = $conexion->prepare("SELECT nombre_archivo, tipo, estado, observaciones FROM registro_documentos WHERE id_usuario = :id");
$stmt->execute([':id' => $id_usuario]);
$docs_subidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$requisitos = [
    'Cedula de Identidad (Laminada y Copia)' => 'cedula',
    'Cedula de Identidad de los Padres' => 'cedula_padres',
    'Partida de Nacimiento' => 'partida_nacimiento',
    'Titulo de Bachiller' => 'titulo_bachiller',
    'Notas Certificadas' => 'notas_certificadas',
    'Constancia OPSU (RUSNIEU)' => 'opsu',
    'Carnet de Inscripcion Militar' => 'inscripcion_militar',
    'Carta de Residencia/Buena Conducta' => 'residencia'
];

function tipoColor($tipo) {
    switch ($tipo) {
        case 'PDF': return ['bg'=>'#fee2e2','text'=>'#dc2626','icon'=>'fa-file-pdf'];
        case 'Word': return ['bg'=>'#dbeafe','text'=>'#2563eb','icon'=>'fa-file-word'];
        case 'Imagen': return ['bg'=>'#dcfce7','text'=>'#16a34a','icon'=>'fa-file-image'];
        default: return ['bg'=>'#f1f5f9','text'=>'#475569','icon'=>'fa-file'];
    }
}
?>

<div class="container py-5 mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background: #ffffff;">
                <div class="row g-0">
                    <div class="col-md-4 bg-primary text-white p-5" style="background: linear-gradient(135deg, #003366 0%, #001f3f 100%) !important;">
                        <h2 class="fw-bold mb-4">Progreso de Registro</h2>
                        <div class="progress mb-4" style="height: 10px; background: rgba(255,255,255,0.2);">
                            <div id="mainProgressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                        </div>
                        <p class="small opacity-75">Sube cada documento para completar tu expediente digital. Al finalizar, podras imprimir tu planilla.</p>
                        
                        <div class="mt-5">
                            <a href="index.php?page=planilla" target="_blank" class="btn btn-outline-light w-100 rounded-pill fw-bold">
                                <i class="fas fa-file-pdf me-2"></i> Ver Planilla
                            </a>
                        </div>
                    </div>

                    <div class="col-md-8 p-5">
                        <h3 class="fw-bold text-dark mb-4">Lista de Documentos Requeridos</h3>
                        
                        <div class="checklist">
                            <?php foreach ($requisitos as $label => $id): 
                                $doc_data = null;
                                foreach($docs_subidos as $d) {
                                    if (stripos($d['nombre_archivo'], $label) !== false || stripos($d['nombre_archivo'], $id) !== false) {
                                        $doc_data = $d;
                                        break;
                                    }
                                }
                                $is_uploaded = ($doc_data !== null);
                                $is_approved = ($is_uploaded && $doc_data['estado'] == 'Aprobado');
                                $is_rejected = ($is_uploaded && $doc_data['estado'] == 'Rechazado');
                            ?>
                                <div class="requirement-item d-flex align-items-center justify-content-between p-3 mb-3 border rounded-3 <?php 
                                    echo $is_approved ? 'bg-light-success' : ($is_rejected ? 'bg-light-danger' : ($is_uploaded ? 'bg-light-info' : 'bg-white')); 
                                ?>" data-requisito="<?php echo $label; ?>" data-id="<?php echo $id; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="status-icon me-3 <?php 
                                            echo $is_approved ? 'text-success' : ($is_rejected ? 'text-danger' : ($is_uploaded ? 'text-info' : 'text-muted')); 
                                        ?>">
                                            <i class="fas <?php 
                                                echo $is_approved ? 'fa-check-circle' : ($is_rejected ? 'fa-times-circle' : ($is_uploaded ? 'fa-clock' : 'fa-circle-notch')); 
                                            ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="m-0 fw-bold <?php 
                                                echo $is_approved ? 'text-success' : ($is_rejected ? 'text-danger' : ($is_uploaded ? 'text-info' : 'text-dark')); 
                                            ?>"><?php echo $label; ?></h6>
                                            <small class="text-muted d-block"><?php 
                                                if ($is_approved) echo 'Documento Aprobado';
                                                elseif ($is_rejected) echo 'Documento Rechazado';
                                                elseif ($is_uploaded) echo 'En revision por la coordinacion';
                                                else echo 'Pendiente por subir';
                                            ?></small>
                                            <?php
                                                $tc = tipoColor($doc_data['tipo'] ?? '');
                                                $icono = $tc['icon'];
                                            ?>
                                            <?php if ($is_uploaded): ?>
                                                <small class="text-muted d-block mt-1" style="font-size:0.7rem;line-height:1.2;">
                                                    <i class="fas <?= $icono ?> me-1"></i><?= htmlspecialchars($doc_data['nombre_archivo']) ?>
                                                    <span class="badge rounded-pill px-2 py-0 ms-1 fw-bold" style="font-size:0.6rem;background:<?= $tc['bg'] ?>;color:<?= $tc['text'] ?>;"><?= $doc_data['tipo'] ?></span>
                                                </small>
                                            <?php endif; ?>
                                            <?php if ($is_rejected && !empty($doc_data['observaciones'])): ?>
                                                <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small border-0" style="font-size: 0.75rem;">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> <strong>Motivo:</strong> <?php echo htmlspecialchars($doc_data['observaciones']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="action-zone">
                                        <?php if ($is_approved): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check me-1"></i> Aprobado</span>
                                        <?php elseif ($is_rejected || !$is_uploaded): ?>
                                            <button class="btn btn-sm <?php echo $is_rejected ? 'btn-danger' : 'btn-primary'; ?> rounded-pill px-3" onclick="triggerUpload('<?php echo $label; ?>')">
                                                <i class="fas <?php echo $is_rejected ? 'fa-redo' : 'fa-upload'; ?> me-1"></i> <?php echo $is_rejected ? 'Reintentar' : 'Subir'; ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-info text-white rounded-pill px-3 py-2"><i class="fas fa-search me-1"></i> En Revision</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" class="file-input-hidden" style="display:none" onchange="handleFileSelected(this, '<?php echo $label; ?>')" accept="image/*,.pdf,.doc,.docx">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-5 pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><i class="fas fa-shield-alt me-1"></i> Sistema Seguro SICEU</span>
                            <a href="index.php?page=login&msg=registro_completo" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm" id="btnFinalizar" style="display: <?php 
                                $all_uploaded = (count($docs_subidos) >= count($requisitos));
                                echo $all_uploaded ? 'block' : 'none';
                            ?>;">
                                <i class="fas fa-flag-checkered me-2"></i> Finalizar Registro
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .requirement-item { transition: all 0.3s ease; border: 1px solid #e2e8f0; }
    .requirement-item:hover { transform: translateX(5px); border-color: #003366; }
    .bg-light-success { background-color: #f0fff4; border-color: #c6f6d5 !important; }
    .bg-light-danger { background-color: #fff5f5; border-color: #feb2b2 !important; }
    .bg-light-info { background-color: #ebf8ff; border-color: #bee3f8 !important; }
    .text-success { color: #2f855a !important; }
    .text-danger { color: #c53030 !important; }
    .text-info { color: #2b6cb0 !important; }
    .status-icon { font-size: 1.5rem; }
</style>

<script>
function tipoBadge(tipo) {
    var map = {
        'PDF': {bg:'#fee2e2', text:'#dc2626', icon:'fa-file-pdf'},
        'Word': {bg:'#dbeafe', text:'#2563eb', icon:'fa-file-word'},
        'Imagen': {bg:'#dcfce7', text:'#16a34a', icon:'fa-file-image'}
    };
    return map[tipo] || {bg:'#f1f5f9', text:'#475569', icon:'fa-file'};
}

function triggerUpload(requisito) {
    const items = document.querySelectorAll('.requirement-item');
    items.forEach(item => {
        if (item.getAttribute('data-requisito') === requisito) {
            item.querySelector('.file-input-hidden').click();
        }
    });
}

function handleFileSelected(input, requisito) {
    if (input.files && input.files[0]) {
        uploadFile(input.files[0], requisito);
    }
}

async function uploadFile(file, requisito) {
    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('requisito', requisito);
    var item = document.querySelector(`.requirement-item[data-requisito="${requisito}"]`);
    const reqId = item ? item.getAttribute('data-id') : '';
    formData.append('requisito_id', reqId);
    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

    item = document.querySelector(`.requirement-item[data-requisito="${requisito}"]`);
    const actionZone = item.querySelector('.action-zone');
    const originalContent = actionZone.innerHTML;
    
    actionZone.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>';

    try {
        const response = await fetch('index.php?action=subir_individual', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            item.className = 'requirement-item d-flex align-items-center justify-content-between p-3 mb-3 border rounded-3 bg-light-info';
            item.querySelector('.status-icon').innerHTML = '<i class="fas fa-clock"></i>';
            item.querySelector('.status-icon').className = 'status-icon me-3 text-info';
            item.querySelector('h6').className = 'm-0 fw-bold text-info';
            var statusText = item.querySelector('h6 + small');
            if (statusText) statusText.textContent = 'En revision por la coordinacion';
            var oldInfo = item.querySelector('h6 + small + small.file-info') || item.querySelector('small.file-info');
            if (oldInfo) oldInfo.remove();
            var infoDiv = item.querySelector('h6').parentElement;
            var fileInfo = document.createElement('small');
            fileInfo.className = 'text-muted d-block mt-1 file-info';
            fileInfo.style.cssText = 'font-size:0.7rem;line-height:1.2;';
            var tb = tipoBadge(result.tipo || 'PDF');
            fileInfo.innerHTML = '<i class="fas ' + tb.icon + ' me-1"></i>' + (result.archivo || 'Documento') + ' <span class="badge rounded-pill px-2 py-0 ms-1 fw-bold" style="font-size:0.6rem;background:' + tb.bg + ';color:' + tb.text + ';">' + (result.tipo || 'PDF') + '</span>';
            infoDiv.appendChild(fileInfo);
            actionZone.innerHTML = '<span class="badge bg-info text-white rounded-pill px-3 py-2"><i class="fas fa-search me-1"></i> En Revision</span>';
            updateProgress();
        } else {
            notificar('Error al subir: ' + (result.message || 'Error desconocido'), 'error');
            actionZone.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('Error:', error);
        notificar('Hubo un problema al subir el archivo.', 'error');
        actionZone.innerHTML = originalContent;
    }
}

function updateProgress() {
    const total = document.querySelectorAll('.requirement-item').length;
    const completed = document.querySelectorAll('.requirement-item .action-zone .badge').length;
    const percent = Math.round((completed / total) * 100);
    
    document.getElementById('mainProgressBar').style.width = percent + '%';
    
    if (percent >= 100) {
        document.getElementById('btnFinalizar').style.display = 'block';
    } else {
        document.getElementById('btnFinalizar').style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', updateProgress);
</script>

