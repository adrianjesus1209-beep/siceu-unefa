<?php
// PRELACION DE MATERIAS
$pdf_url = URLROOT . '/assets/pensum/matriz-prelacion-sistemas-diurno-2010.pdf';
?>
<div class="container py-5 mt-4">
    <div class="text-center mb-5 fluido-entrada">
        <span class="insignia-seccion">Planificacion academica</span>
        <h1 class="display-4 fw-bold mb-3" style="color: #003366;">Matriz de Prelacion</h1>
        <p class="lead text-muted mx-auto" style="max-width: 600px;">Relacion de asignaturas requisito para cursar cada materia - Ingenieria de Sistemas</p>
        <div class="mt-4">
            <a href="<?php echo $pdf_url; ?>" download class="btn btn-outline-danger rounded-pill px-4 fw-bold">
                <i class="fas fa-file-pdf me-2"></i>Descargar Matriz (PDF)
            </a>
        </div>
    </div>

    <div class="row justify-content-center fluido-entrada retraso-1">
        <div class="col-12">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-0" style="background: #f8f9fa;">
                    <div class="ratio" style="--bs-aspect-ratio: 140%;">
                        <iframe src="<?php echo $pdf_url; ?>" style="border: none; width: 100%; height: 100%;" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="card-footer bg-white text-center p-3 border-0">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Si no visualizas el PDF, <a href="<?php echo $pdf_url; ?>" target="_blank" class="text-decoration-none fw-bold">haz clic aqui para abrirlo</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
