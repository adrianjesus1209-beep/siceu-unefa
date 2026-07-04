/**
 * SICEU UNEFA - Utilidades Compartidas
 */

// Toggle password visibility
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/* ===== Toast Notification System ===== */
function notificar(mensaje, tipo) {
    tipo = tipo || 'info';
    const contenedor = document.getElementById('toast-container-unefa');
    if (!contenedor) return;

    const iconos = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    const toast = document.createElement('div');
    toast.className = 'toast-unefa toast-' + tipo;
    toast.innerHTML =
        '<i class="fas ' + (iconos[tipo] || iconos.info) + ' toast-icon"></i>' +
        '<div class="toast-body">' + mensaje + '</div>' +
        '<button class="toast-close" onclick="cerrarToast(this.parentElement)"><i class="fas fa-times"></i></button>';

    contenedor.appendChild(toast);

    setTimeout(function() {
        cerrarToast(toast);
    }, 5000);
}

function cerrarToast(toast) {
    if (!toast || toast.classList.contains('toast-hiding')) return;
    toast.classList.add('toast-hiding');
    setTimeout(function() {
        if (toast.parentElement) toast.remove();
    }, 300);
}

/* ===== Confirmation Modal ===== */
function confirmar(mensaje, titulo) {
    return new Promise(function(resolve) {
        var modalEl = document.getElementById('modalConfirmar');
        if (!modalEl) {
            resolve(true);
            return;
        }
        var tituloEl = document.getElementById('confirmar-titulo');
        var mensajeEl = document.getElementById('confirmar-mensaje');
        var btnAceptar = document.getElementById('confirmar-aceptar');
        var btnCancelar = document.getElementById('confirmar-cancelar');

        if (tituloEl) tituloEl.textContent = titulo || 'Confirmar';
        if (mensajeEl) mensajeEl.innerHTML = mensaje;

        function limpiar() {
            btnAceptar.removeEventListener('click', onAceptar);
            btnCancelar.removeEventListener('click', onCancelar);
            modalEl.removeEventListener('hidden.bs.modal', onCancelar);
        }

        function onAceptar() {
            limpiar();
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            resolve(true);
        }

        function onCancelar() {
            limpiar();
            resolve(false);
        }

        btnAceptar.addEventListener('click', onAceptar);
        btnCancelar.addEventListener('click', onCancelar);
        modalEl.addEventListener('hidden.bs.modal', onCancelar);

        var modal = new bootstrap.Modal(modalEl, { backdrop: false });
        modal.show();
    });
}

// Playlist video handler
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="playlist"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.slot-video video').forEach(vid => {
                vid.pause();
            });
            const activeVideo = document.querySelector('.slot-video:not([style*="display: none"]) video, .slot-video[style*="display: block"] video');
            if (activeVideo) activeVideo.load();
        });
    });

    // Scroll Reveal with Intersection Observer
    const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    if (revealElements.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        revealElements.forEach(el => observer.observe(el));
    }
});
