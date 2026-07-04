<?php // INICIO DE SESION ?>
<div class="container pt-5 pb-4">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="bg-white p-3 rounded-4 shadow-premium border-light">
                <div class="text-center mb-3">
                    <img src="<?php echo URLROOT; ?>/assets/img/logos/logo_unefa.png" alt="Logo UNEFA" class="rounded-circle mb-2" style="width: 64px; height: 64px; object-fit: contain; border: 2px solid #003366;">
                    <h2 class="fw-bold h5">INICIO DE SESION</h2>
                    <p class="text-muted small">Selecciona tu perfil para continuar</p>
                </div>

                <div id="paso-rol-login">
                    <p class="fw-bold text-center mb-2" style="color: #003366; font-size:0.85rem;">¿Como ingresas al sistema?</p>
                    <div class="row g-2 mb-1">
                        <div class="col-4">
                            <div class="tarjeta-rol-login border rounded-4 p-2 text-center"
                                onclick="seleccionarRolLogin('Estudiante', this)"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div style="font-size: 1.45rem; color: #003366;"><i class="fas fa-user-graduate"></i></div>
                                <div class="fw-bold mt-1" style="font-size: 0.72rem; color: #003366;">Estudiante</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="tarjeta-rol-login border rounded-4 p-2 text-center"
                                onclick="seleccionarRolLogin('Docente', this)"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div style="font-size: 1.45rem; color: #7b1fa2;"><i class="fas fa-chalkboard-teacher"></i></div>
                                <div class="fw-bold mt-1" style="font-size: 0.72rem; color: #7b1fa2;">Docente</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="tarjeta-rol-login border rounded-4 p-2 text-center"
                                onclick="seleccionarRolLogin('Coordinador', this)"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div style="font-size: 1.45rem; color: #e67e22;"><i class="fas fa-user-shield"></i></div>
                                <div class="fw-bold mt-1" style="font-size: 0.72rem; color: #e67e22;">Coordinador</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="paso-form-login" style="display:none;">
                    <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded-3" id="login-rol-badge" style="border: 1px solid rgba(0,0,0,0.05); transition: all 0.3s ease;">
                        <span id="login-rol-icono" style="font-size: 1.3rem;"></span>
                        <div>
                            <div class="fw-bold small" id="login-rol-texto"></div>
                            <a href="#" onclick="cambiarRolLogin()" class="text-decoration-none hover-link" style="font-size: 10px; color: #64748b; font-weight: 600;">
                                <i class="fas fa-sync-alt me-1"></i>Cambiar perfil
                            </a>
                        </div>
                    </div>

                    <form action="index.php?action=login" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="rol" id="campo-rol-login" value="">
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Correo Institucional</label>
                            <div class="input-group-modern">
                                <i class="fas fa-envelope icon-prefix"></i>
                                <input type="email" name="correo" class="form-control-modern" placeholder="usuario@unefa.edu.ve" required style="padding:0.6rem 0.75rem 0.6rem 2.2rem;">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Contrasena</label>
                            <div class="position-relative input-group-modern">
                                <i class="fas fa-lock icon-prefix"></i>
                                <input type="password" name="clave" id="clave-login" class="form-control-modern pe-5" placeholder="••••••••" required minlength="8" style="padding:0.6rem 2.5rem 0.6rem 2.2rem;">
                                <button type="button" class="btn-eye-float" onclick="togglePass('clave-login', this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="form-check">
                            </div>
                            <a href="index.php?page=reset_password" class="text-decoration-none small fw-bold" style="color: #003366;">
                                ¿Olvidaste tu contrasena?
                            </a>
                        </div>

                        <button type="submit" class="btn btn-modern w-100 py-2 fw-bold shadow-sm" id="btn-ingresar">
                            Ingresar al Sistema
                        </button>
                    </form>
                </div>

                <div class="text-center mt-2" id="registro-link">
                    <div class="small text-muted mb-1">¿No tienes cuenta en el sistema?</div>
                    <a href="index.php?page=register" class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-bold" style="color: #003366; border-color: #003366;">
                        REGISTRATE AQUI
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
var configuracionRoles = {
    'Estudiante': { color: '#003366', bg: '#e8eeff', icon: '<i class="fas fa-user-graduate"></i>', label: 'Accediendo como Estudiante' },
    'Docente': { color: '#7b1fa2', bg: '#f3e5f5', icon: '<i class="fas fa-chalkboard-teacher"></i>', label: 'Accediendo como Docente' },
    'Coordinador': { color: '#e67e22', bg: '#fdf2e9', icon: '<i class="fas fa-user-shield"></i>', label: 'Accediendo como Coordinador' }
};

function seleccionarRolLogin(rol, elemento) {
    var cfg = configuracionRoles[rol];
    document.getElementById('campo-rol-login').value = rol;
    document.getElementById('login-rol-icono').innerHTML = cfg.icon;
    document.getElementById('login-rol-icono').style.color = cfg.color;
    document.getElementById('login-rol-texto').textContent = cfg.label;
    document.getElementById('login-rol-texto').style.color = cfg.color;
    document.getElementById('login-rol-badge').style.background = cfg.bg;
    document.getElementById('login-rol-badge').style.borderColor = cfg.color;
    document.getElementById('btn-ingresar').style.background = cfg.color;

    document.querySelectorAll('.tarjeta-rol-login').forEach(function(c) { c.classList.remove('seleccionada'); });
    elemento.classList.add('seleccionada');

    document.getElementById('registro-link').style.display = (rol === 'Estudiante') ? 'block' : 'none';

    setTimeout(function() {
        document.getElementById('paso-rol-login').style.display = 'none';
        document.getElementById('paso-form-login').style.display = 'block';
    }, 300);
}

function cambiarRolLogin() {
    document.getElementById('paso-form-login').style.display = 'none';
    document.getElementById('paso-rol-login').style.display = 'block';
    document.getElementById('registro-link').style.display = 'block';
}
</script>

