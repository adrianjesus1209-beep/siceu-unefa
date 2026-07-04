<?php
// INICIO DEL SISTEMA
session_start();

// SEGURIDAD DE SESION
if (!isset($_SESSION['INIT'])) {
    session_regenerate_id(true);
    $_SESSION['INIT'] = true;
}
date_default_timezone_set('America/Caracas');
define('URLROOT', 'public');
require_once 'app/core/Database.php';
require_once 'app/controllers/AuthController.php';
require_once 'app/helpers/AuditHelper.php';
require_once 'app/helpers/ReportesPDF.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// FUNCIONES AUXILIARES
function verificarRateLimit($conexion, $tipo = 'login', $max_intentos = 5, $ventana_minutos = 15) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conexion->prepare(
        "SELECT COUNT(*) FROM intentos_fallidos
         WHERE direccion_ip = :ip AND tipo = :tipo
         AND fecha_intento > DATE_SUB(NOW(), INTERVAL :ventana MINUTE)"
    );
    $stmt->execute([':ip' => $ip, ':tipo' => $tipo, ':ventana' => $ventana_minutos]);
    return (int)$stmt->fetchColumn() >= $max_intentos;
}

function registrarIntentoFallido($conexion, $tipo = 'login', $identificador = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conexion->prepare(
        "INSERT INTO intentos_fallidos (direccion_ip, tipo, identificador) VALUES (:ip, :tipo, :ident)"
    );
    $stmt->execute([':ip' => $ip, ':tipo' => $tipo, ':ident' => $identificador]);
}

function limpiarIntentosFallidos($conexion, $tipo = 'login') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conexion->prepare(
        "DELETE FROM intentos_fallidos WHERE direccion_ip = :ip AND tipo = :tipo"
    );
    $stmt->execute([':ip' => $ip, ':tipo' => $tipo]);
}

function labelSemestre($semestre_actual) {
    $labels = [0 => 'CINU', 1 => '1°', 2 => '2°', 3 => '3°', 4 => '4°', 5 => '5°', 6 => '6°', 7 => '7°', 8 => '8°'];
    $idx = (int)($semestre_actual ?? 0);
    return $labels[$idx] ?? ($idx + 1) . '°';
}

// VALIDACION CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    $token_enviado = $_POST['csrf_token'] ?? '';
    if (empty($token_enviado) || !hash_equals($_SESSION['csrf_token'], $token_enviado)) {
        $_SESSION['error'] = "Error de seguridad: Token CSRF invalido. Intente de nuevo.";
        header('Location: index.php?page=login');
        exit;
    }
}

$base_datos = new Database();
$conexion = $base_datos->getConnection();
$controlador_auth = new AuthController($conexion);

if (isset($_SESSION['id_usuario'])) {
    $stmtCheck = $conexion->prepare("SELECT estado FROM usuario WHERE id = :id LIMIT 1");
    $stmtCheck->execute([':id' => $_SESSION['id_usuario']]);
    $estado_actual = $stmtCheck->fetchColumn();
    if ($estado_actual === 'Inactivo') {
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }
}

// PERIODO ACADEMICO
function periodoPermiteInscripciones($conexion) {
    $stmt = $conexion->query("SELECT id, estado, nombre FROM periodo_academico ORDER BY FIELD(estado, 'Activo', 'Planificado', 'Finalizado') LIMIT 1");
    $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$periodo) {
        $_SESSION['error'] = "No hay un periodo academico configurado.";
        return false;
    }
    if ($periodo['estado'] !== 'Activo') {
        $_SESSION['error'] = "El periodo academico «{$periodo['nombre']}» esta {$periodo['estado']}. No se permiten inscripciones en este momento.";
        return false;
    }
    return $periodo['id'];
}

$accion = $_GET['action'] ?? null;
// ENRUTADOR DE ACCIONES
if ($accion) {
    switch ($accion) {
        // CARNETIZACION
        case 'save_photo':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
                exit;
            }
            $body     = json_decode(file_get_contents('php://input'), true);
            $est_id   = intval($body['est_id']   ?? 0);
            $photoB64 = $body['photo']            ?? '';
            $csrf_in  = $body['csrf_token']       ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $csrf_in) || $est_id === 0 || empty($photoB64)) {
                echo json_encode(['success' => false, 'message' => 'Datos invalidos.']);
                exit;
            }

            $stmtCheck = $conexion->prepare("SELECT estado FROM usuario WHERE id = :id LIMIT 1");
            $stmtCheck->execute([':id' => $est_id]);
            $estado_actual = $stmtCheck->fetchColumn();
            
            if ($estado_actual !== 'Aprobado') {
                echo json_encode(['success' => false, 'message' => 'No autorizado: El estudiante debe estar aprobado para ser carnetizado.']);
                exit;
            }

            if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $photoB64)) {
                echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido. Use JPG o PNG.']);
                exit;
            }

            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $photoB64);
            $decoded   = base64_decode($imageData);
            if (!$decoded) {
                echo json_encode(['success' => false, 'message' => 'Imagen invalida.']);
                exit;
            }
            $dir      = 'public/uploads/profiles/';
            $filename = 'est_' . $est_id . '_' . time() . '.jpg';
            $filepath = $dir . $filename;
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $stmtOld = $conexion->prepare("SELECT foto_perfil FROM perfil p JOIN usuario u ON u.id_perfil = p.id WHERE u.id = :id LIMIT 1");
            $stmtOld->execute([':id' => $est_id]);
            $old = $stmtOld->fetchColumn();
            if ($old && file_exists($dir . $old)) @unlink($dir . $old);
            file_put_contents($filepath, $decoded);
            // Actualizar BD
            $stmtUp = $conexion->prepare(
                "UPDATE perfil p JOIN usuario u ON u.id_perfil = p.id
                 SET p.foto_perfil = :pic, p.fecha_carnetizacion = NOW()
                 WHERE u.id = :id"
            );
            $stmtUp->execute([':pic' => $filename, ':id' => $est_id]);
            AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Carnetizacion', "Carnetizo al estudiante ID $est_id");
            echo json_encode(['success' => true]);
            exit;
            break;

        case 'save_teacher_photo':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
                exit;
            }
            $body     = json_decode(file_get_contents('php://input'), true);
            $doc_id   = intval($body['doc_id']   ?? 0);
            $photoB64 = $body['photo']            ?? '';
            $csrf_in  = $body['csrf_token']       ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $csrf_in) || $doc_id === 0 || empty($photoB64)) {
                echo json_encode(['success' => false, 'message' => 'Datos invalidos.']);
                exit;
            }

            if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $photoB64)) {
                echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido. Use JPG o PNG.']);
                exit;
            }

            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $photoB64);
            $decoded   = base64_decode($imageData);
            if (!$decoded) {
                echo json_encode(['success' => false, 'message' => 'Imagen invalida.']);
                exit;
            }
            $dir      = 'public/uploads/profiles/';
            $filename = 'doc_' . $doc_id . '_' . time() . '.jpg';
            $filepath = $dir . $filename;
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $stmtOld = $conexion->prepare("SELECT foto_perfil FROM perfil p JOIN usuario u ON u.id_perfil = p.id WHERE u.id = :id LIMIT 1");
            $stmtOld->execute([':id' => $doc_id]);
            $old = $stmtOld->fetchColumn();
            if ($old && file_exists($dir . $old)) @unlink($dir . $old);
            file_put_contents($filepath, $decoded);
            $stmtUp = $conexion->prepare(
                "UPDATE perfil p JOIN usuario u ON u.id_perfil = p.id
                 SET p.foto_perfil = :pic, p.fecha_carnetizacion = NOW()
                 WHERE u.id = :id"
            );
            $stmtUp->execute([':pic' => $filename, ':id' => $doc_id]);
            AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Carnetizacion', "Carnetizo al docente ID $doc_id");
            echo json_encode(['success' => true]);
            exit;
            break;

        case 'simulate_expiry':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
                exit;
            }
            $body   = json_decode(file_get_contents('php://input'), true);
            $est_id = intval($body['est_id'] ?? 0);
            $type   = $body['type'] ?? 'total';
            
            if ($est_id === 0) {
                echo json_encode(['success' => false, 'message' => 'ID invalido.']);
                exit;
            }

            $date = $type === 'reset' ? date('Y-m-d H:i:s') : ($type === 'window' ? date('Y-m-d H:i:s', strtotime('-8 days')) : date('Y-m-d H:i:s', strtotime('-13 months')));

            $stmt = $conexion->prepare(
                "UPDATE perfil p JOIN usuario u ON u.id_perfil = p.id
                 SET p.fecha_carnetizacion = :date
                 WHERE u.id = :id"
            );
            $stmt->execute([':date' => $date, ':id' => $est_id]);
            echo json_encode(['success' => true]);
            exit;
            break;
        // AUTENTICACION
        case 'login':
            if (verificarRateLimit($conexion, 'login', 5, 15)) {
                $_SESSION['error'] = "Demasiados intentos fallidos. Espere 15 minutos.";
                header('Location: index.php?page=login');
                exit;
            }
            $controlador_auth->ingresar();
            break;
        case 'register':
            if (!periodoPermiteInscripciones($conexion)) {
                header('Location: index.php?page=login');
                exit;
            }
            $controlador_auth->registrar();
            break;
        case 'upload_docs': $controlador_auth->subirDocumentos(); break;
        case 'subir_individual': $controlador_auth->subirIndividual(); break;
        case 'reset_password':
            if (verificarRateLimit($conexion, 'reset_password', 5, 15)) {
                $_SESSION['error'] = "Demasiados intentos de recuperacion. Espere 15 minutos.";
                header('Location: index.php?page=reset_password');
                exit;
            }
            $controlador_auth->resetPassword();
            break;
        case 'logout': $controlador_auth->salir(); break;
        case 'update_student_profile':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    if (isset($_GET['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Error de seguridad.']);
                        exit;
                    }
                    header('Location: index.php?page=approve_registration');
                    exit;
                }
                $id_perfil = $_POST['id_perfil'] ?? null;
                if ($id_perfil) {
                    $checkRol = $conexion->prepare("SELECT u.rol FROM usuario u WHERE u.id_perfil = :pid LIMIT 1");
                    $checkRol->execute([':pid' => $id_perfil]);
                    $rolDestino = $checkRol->fetchColumn();
                    if (!in_array($rolDestino, ['Estudiante', 'Docente'])) {
                        if (isset($_GET['ajax'])) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => 'Rol no permitido.']);
                            exit;
                        }
                        header('Location: index.php?page=documentos_usuarios');
                        exit;
                    }
                }
                $datos = [
                    'cedula' => trim($_POST['cedula'] ?? ''),
                    'nombre' => trim($_POST['nombre'] ?? ''),
                    'segundo_nombre' => trim($_POST['segundo_nombre'] ?? ''),
                    'apellido' => trim($_POST['apellido'] ?? ''),
                    'segundo_apellido' => trim($_POST['segundo_apellido'] ?? ''),
                    'telefono' => trim($_POST['telefono'] ?? ''),
                    'direccion' => trim($_POST['direccion'] ?? '')
                ];
                if ($id_perfil && !empty($datos['cedula']) && !empty($datos['nombre']) && !empty($datos['apellido'])) {
                    $stmtOld = $conexion->prepare("SELECT cedula FROM perfil WHERE id = :id");
                    $stmtOld->execute([':id' => $id_perfil]);
                    $oldCedula = $stmtOld->fetchColumn();

                    require_once 'app/models/Usuario.php';
                    $modelo = new Usuario($conexion);
                    $modelo->actualizarPerfilCompleto($id_perfil, $datos);

                    if ($oldCedula && $datos['cedula'] && $oldCedula !== $datos['cedula']) {
                        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
                        $oldNum = preg_replace('/[^0-9]/', '', $oldCedula);
                        $newNum = preg_replace('/[^0-9]/', '', $datos['cedula']);
                        $oldDir = $baseDir . DIRECTORY_SEPARATOR . $oldNum;
                        $newDir = $baseDir . DIRECTORY_SEPARATOR . $newNum;
                        if ($oldNum !== $newNum && is_dir($oldDir)) {
                            rename($oldDir, $newDir);
                        }
                        $oldPattern = 'public/uploads/' . $oldNum . '/';
                        $newPattern = 'public/uploads/' . $newNum . '/';
                        $upd = $conexion->prepare(
                            "UPDATE registro_documentos SET ruta = REPLACE(ruta, :old, :new) WHERE ruta LIKE :like"
                        );
                        $upd->execute([':old' => $oldPattern, ':new' => $newPattern, ':like' => $oldPattern . '%']);
                    }

                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Actualizar Perfil', "Actualizo perfil ID $id_perfil ({$datos['nombre']} {$datos['apellido']})");
                    if (isset($_GET['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    header('Location: index.php?page=approve_registration&msg=profile_updated');
                } else {
                    if (isset($_GET['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
                        exit;
                    }
                    $_SESSION['error'] = "Datos incompletos.";
                    header('Location: index.php?page=approve_registration');
                }
            }
            exit;
            break;
        // GESTION DE DOCENTES
        case 'register_teacher':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                if (!periodoPermiteInscripciones($conexion)) {
                    header('Location: index.php?page=gestion_docentes');
                    exit;
                }
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=gestion_docentes');
                    exit;
                }
                $clave = $_POST['clave'] ?? '';
                if (strlen($clave) < 8) {
                    $_SESSION['error'] = "La clave debe tener al menos 8 caracteres.";
                    header('Location: index.php?page=gestion_docentes');
                    exit;
                }
                $id_materia = intval($_POST['id_materia'] ?? 0);
                $seccion_num = intval($_POST['seccion_num'] ?? 1);
                $id_seccion_reasignar = intval($_POST['id_seccion_reasignar'] ?? 0);

                require_once 'app/models/Usuario.php';
                $modelo = new Usuario($conexion);
                $datos = [
                    'tipo_documento' => trim($_POST['tipo_documento'] ?? 'V'),
                    'cedula'         => trim(preg_replace('/[^0-9]/', '', $_POST['cedula'] ?? '')),
                    'nombre'         => trim($_POST['nombre'] ?? ''),
                    'segundo_nombre' => trim($_POST['segundo_nombre'] ?? ''),
                    'apellido'       => trim($_POST['apellido'] ?? ''),
                    'segundo_apellido' => trim($_POST['segundo_apellido'] ?? ''),
                    'telefono'       => trim(preg_replace('/[^0-9]/', '', $_POST['telefono'] ?? '')),
                    'direccion'      => null,
                    'correo'         => trim($_POST['correo'] ?? ''),
                    'clave'          => $clave,
                    'rol'            => 'Docente',
                    'id_carrera'     => null,
                    'preguntas'      => []
                ];
                if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['cedula']) || empty($datos['correo'])) {
                    $_SESSION['error'] = "Todos los campos obligatorios deben estar completos.";
                    header('Location: index.php?page=gestion_docentes');
                    exit;
                }
                if (strlen($datos['cedula']) < 6 || strlen($datos['cedula']) > 9) {
                    $_SESSION['error'] = "La cedula debe tener entre 6 y 9 digitos.";
                    header('Location: index.php?page=gestion_docentes');
                    exit;
                }
                try {
                    if ($modelo->registrar($datos)) {
                        $modelo->actualizarEstadoUsuarioPorCorreo($datos['correo'], 'Aprobado');

                        if ($id_materia > 0 || $id_seccion_reasignar > 0) {
                            $user_info = $modelo->obtenerUsuarioPorCorreo($datos['correo']);
                            if ($user_info) {
                                $id_docente = $user_info['id'];

                                if ($id_seccion_reasignar > 0) {
                                    $stmtSec = $conexion->prepare("UPDATE seccion SET id_docente = ? WHERE id = ?");
                                    $stmtSec->execute([$id_docente, $id_seccion_reasignar]);
                                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Reasignar Materia',
                                        "Registro docente {$datos['nombre']} {$datos['apellido']} y reasigno seccion ID $id_seccion_reasignar");
                                } else {
                                    $stmtSem = $conexion->prepare("SELECT semestre FROM materia WHERE id = :id LIMIT 1");
                                    $stmtSem->execute([':id' => $id_materia]);
                                    $semestre = $stmtSem->fetchColumn();

                                    if ($semestre === false || $semestre === null) {
                                        $semestre_str = "E";
                                    } else {
                                        $semestre_str = (string)$semestre;
                                    }

                                    if ($seccion_num === 1) {
                                        $nombre_seccion = "0" . $semestre_str . "s-2629-D1";
                                    } else {
                                        $nombre_seccion = "0" . $semestre_str . "-2629-D2";
                                    }

                                    $stmtSec = $conexion->prepare("INSERT INTO seccion (id_materia, id_docente, nombre_seccion) VALUES (?, ?, ?)");
                                    $stmtSec->execute([$id_materia, $id_docente, $nombre_seccion]);
                                }
                            }
                        }

                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Registro Docente', "Registro docente: {$datos['nombre']} {$datos['apellido']} ({$datos['correo']})");
                        header('Location: index.php?page=gestion_docentes&msg=teacher_registered');
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                    header('Location: index.php?page=gestion_docentes');
                }
            }
            exit;
            break;
        case 'assign_teacher_subject':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                header('Content-Type: application/json');
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    echo json_encode(['success' => false, 'message' => 'Error de seguridad.']);
                    exit;
                }
                $id_docente = intval($_POST['id_docente'] ?? 0);
                $id_materia = intval($_POST['id_materia'] ?? 0);
                $seccion_num = intval($_POST['seccion_num'] ?? 1);
                if ($id_docente <= 0 || $id_materia <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
                    exit;
                }
                $check = $conexion->prepare("SELECT id, rol FROM usuario WHERE id = :id AND rol = 'Docente' LIMIT 1");
                $check->execute([':id' => $id_docente]);
                if (!$check->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Docente no encontrado.']);
                    exit;
                }
                $dup = $conexion->prepare("SELECT id FROM seccion WHERE id_docente = :doc AND id_materia = :mat LIMIT 1");
                $dup->execute([':doc' => $id_docente, ':mat' => $id_materia]);
                if ($dup->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'El docente ya tiene asignada esta materia.']);
                    exit;
                }
                $stmtSem = $conexion->prepare("SELECT semestre FROM materia WHERE id = :id LIMIT 1");
                $stmtSem->execute([':id' => $id_materia]);
                $semestre = $stmtSem->fetchColumn();
                if ($semestre === false) {
                    echo json_encode(['success' => false, 'message' => 'Materia no encontrada.']);
                    exit;
                }
                $semestre_str = ($semestre === null) ? 'E' : (string)$semestre;
                $nombre_seccion = ($seccion_num === 1)
                    ? "0{$semestre_str}s-2629-D1"
                    : "0{$semestre_str}-2629-D2";
                try {
                    $stmtSec = $conexion->prepare("INSERT INTO seccion (id_materia, id_docente, nombre_seccion) VALUES (?, ?, ?)");
                    $stmtSec->execute([$id_materia, $id_docente, $nombre_seccion]);
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Asignar Materia', "Asigno materia ID $id_materia al docente ID $id_docente");
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error al asignar: ' . $e->getMessage()]);
                }
                exit;
            }
            exit;
            break;
        case 'reassign_teacher_subject':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                header('Content-Type: application/json');
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    echo json_encode(['success' => false, 'message' => 'Error de seguridad.']);
                    exit;
                }
                $id_seccion = intval($_POST['id_seccion'] ?? 0);
                $id_nuevo_docente = intval($_POST['id_nuevo_docente'] ?? 0);
                if ($id_seccion <= 0 || $id_nuevo_docente <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
                    exit;
                }
                try {
                    $stmtSec = $conexion->prepare(
                        "SELECT s.id_docente as old_docente_id,
                                CONCAT(p.nombre, ' ', p.apellido) as docente_actual,
                                CONCAT(m.codigo_materia, ' - ', m.nombre_materia) as materia_info
                        FROM seccion s
                        JOIN usuario u ON s.id_docente = u.id
                        JOIN perfil p ON u.id_perfil = p.id
                        JOIN materia m ON s.id_materia = m.id
                        WHERE s.id = :id LIMIT 1"
                    );
                    $stmtSec->execute([':id' => $id_seccion]);
                    $seccion_info = $stmtSec->fetch(PDO::FETCH_ASSOC);

                    if (!$seccion_info) {
                        echo json_encode(['success' => false, 'message' => 'Seccion no encontrada.']);
                        exit;
                    }

                    $check = $conexion->prepare(
                        "SELECT CONCAT(p.nombre, ' ', p.apellido) as nombre_docente
                        FROM usuario u JOIN perfil p ON u.id_perfil = p.id
                        WHERE u.id = :id AND u.rol = 'Docente' LIMIT 1"
                    );
                    $check->execute([':id' => $id_nuevo_docente]);
                    $nuevo_docente = $check->fetchColumn();

                    if (!$nuevo_docente) {
                        echo json_encode(['success' => false, 'message' => 'Docente no encontrado.']);
                        exit;
                    }

                    if ((int)$seccion_info['old_docente_id'] === $id_nuevo_docente) {
                        echo json_encode(['success' => false, 'message' => 'El docente seleccionado ya tiene esta materia.']);
                        exit;
                    }

                    $stmt = $conexion->prepare("UPDATE seccion SET id_docente = :nuevo WHERE id = :id");
                    $stmt->execute([':nuevo' => $id_nuevo_docente, ':id' => $id_seccion]);

                    if ($stmt->rowCount() > 0) {
                        $detalle = "Reasigno materia {$seccion_info['materia_info']} de {$seccion_info['docente_actual']} a {$nuevo_docente}";
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Reasignar Materia', $detalle);
                        echo json_encode(['success' => true, 'message' => 'Materia reasignada correctamente. Las notas e inscripciones de los estudiantes se conservan.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No se pudo reasignar la materia.']);
                    }
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error al reasignar: ' . $e->getMessage()]);
                }
                exit;
            }
            exit;
            break;
        // INSCRIPCION DE MATERIAS
        case 'enroll_subject':
            if (isset($_SESSION['id_usuario']) && $_SESSION['rol_usuario'] === 'Estudiante') {
                $csrf_token = $_GET['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=login');
                    exit;
                }
                $id_periodo = periodoPermiteInscripciones($conexion);
                if (!$id_periodo) {
                    header('Location: index.php?page=enrollment');
                    exit;
                }
                $id_seccion = $_GET['seccion'] ?? null;
                if ($id_seccion) {
                    $stmt = $conexion->prepare("
                        SELECT s.id, m.id as id_materia, m.semestre, m.nombre_materia, u.rol as docente_rol
                        FROM seccion s 
                        JOIN materia m ON s.id_materia = m.id 
                        JOIN usuario u ON s.id_docente = u.id
                        WHERE s.id = :id
                    ");
                    $stmt->execute([':id' => $id_seccion]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($data) {
                        if ($data['docente_rol'] !== 'Docente') {
                            $_SESSION['error'] = "No se puede inscribir: la seccion no tiene un docente asignado.";
                            header('Location: index.php?page=enrollment');
                            exit;
                        }
                        $id_materia = $data['id_materia'];
                        $sem_target = (int)$data['semestre'];

                        $stmt = $conexion->prepare("
                            SELECT m.id, m.semestre 
                            FROM solicitud_inscripcion si 
                            JOIN seccion s ON si.id_seccion = s.id 
                            JOIN materia m ON s.id_materia = m.id 
                            WHERE si.id_estudiante = :est AND si.estado = 'Aceptada' AND si.nota >= 10
                        ");
                        $stmt->execute([':est' => $_SESSION['id_usuario']]);
                        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $ids_aceptadas = array_column($historial, 'id');

                        $valid_sem = true;
                        if ($sem_target > 0) {
                            $stmt = $conexion->prepare("SELECT id FROM materia WHERE semestre = :s");
                            $stmt->execute([':s' => $sem_target - 1]);
                            $materias_prev = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            $ids_prev = array_column($materias_prev, 'id');
                            if (!empty($ids_prev)) {
                                if (count(array_intersect($ids_prev, $ids_aceptadas)) < count($ids_prev)) {
                                    $valid_sem = false;
                                }
                            }
                        }

                        $stmt = $conexion->prepare("SELECT id_prerrequisito FROM prelacion WHERE id_materia = :id");
                        $stmt->execute([':id' => $id_materia]);
                        $prelaciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $prelaciones_ok = true;
                        foreach ($prelaciones as $pid) {
                            if (!in_array($pid, $ids_aceptadas)) {
                                $prelaciones_ok = false;
                                break;
                            }
                        }

                        $stmt = $conexion->prepare("
                            SELECT SUM(m.uc) as total_uc 
                            FROM solicitud_inscripcion si 
                            JOIN seccion s ON si.id_seccion = s.id 
                            JOIN materia m ON s.id_materia = m.id 
                            WHERE si.id_estudiante = :est AND si.estado IN ('Pendiente', 'Aceptada')
                        ");
                        $stmt->execute([':est' => $_SESSION['id_usuario']]);
                        $total_actual_uc = (int)$stmt->fetchColumn();
                        
                        $stmt = $conexion->prepare("SELECT uc FROM materia WHERE id = :id");
                        $stmt->execute([':id' => $id_materia]);
                        $materia_uc = (int)$stmt->fetchColumn();

                        if ($total_actual_uc + $materia_uc > 18) {
                            $_SESSION['error'] = "Has superado el limite de 18 UC permitidas. Total acumulado: " . ($total_actual_uc + $materia_uc);
                            header('Location: index.php?page=enrollment');
                            exit;
                        }

                        if ($valid_sem && $prelaciones_ok) {
                            $stmt = $conexion->prepare("SELECT id, estado FROM solicitud_inscripcion WHERE id_estudiante = :est AND id_seccion = :sec");
                            $stmt->execute([':est' => $_SESSION['id_usuario'], ':sec' => $id_seccion]);
                            $existente = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (!$existente) {
                                $stmt = $conexion->prepare("INSERT INTO solicitud_inscripcion (id_estudiante, id_seccion, id_periodo, estado, nota) VALUES (:est, :sec, :per, 'Pendiente', 0)");
                                $stmt->execute([':est' => $_SESSION['id_usuario'], ':sec' => $id_seccion, ':per' => $id_periodo]);
                                AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Inscripcion', "Se inscribio en {$data['nombre_materia']} (seccion ID $id_seccion)");
                                $_SESSION['success'] = "Solicitud enviada para " . $data['nombre_materia'];
                            } elseif ($existente['estado'] === 'Rechazada') {
                                $stmtUp = $conexion->prepare("UPDATE solicitud_inscripcion SET estado = 'Pendiente', nota = 0, id_periodo = :per WHERE id = :id");
                                $stmtUp->execute([':id' => $existente['id'], ':per' => $id_periodo]);
                                AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Inscripcion', "Re-aplico en {$data['nombre_materia']} (seccion ID $id_seccion)");
                                $_SESSION['success'] = "Solicitud re-enviada para " . $data['nombre_materia'];
                            }
                        } else {
                            $_SESSION['error'] = "No cumples con las prelaciones o el semestre para cursar esta materia.";
                        }
                    }
                }
            }
            header('Location: index.php?page=enrollment');
            exit;
            break;
        // CALIFICACIONES
        case 'save_grade':
            if (isset($_SESSION['id_usuario']) && in_array($_SESSION['rol_usuario'], ['Docente', 'Coordinador'])) {
                $id_solicitud = $_POST['id_solicitud'] ?? null;
                $nota = $_POST['nota'] ?? null;
                $csrf_token = $_POST['csrf_token'] ?? '';

                if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=login');
                    exit;
                }

                if ($id_solicitud !== null && $nota !== null) {
                    $nota = (int)$nota;
                    if ($nota < 0 || $nota > 20) {
                        $_SESSION['error'] = "La nota debe estar entre 0 y 20.";
                        header('Location: index.php?page=dashboard');
                        exit;
                    }
                    $stmtPer = $conexion->prepare("SELECT pa.estado FROM solicitud_inscripcion si LEFT JOIN periodo_academico pa ON si.id_periodo = pa.id WHERE si.id = :id");
                    $stmtPer->execute([':id' => $id_solicitud]);
                    $estado_periodo = $stmtPer->fetchColumn();
                    if ($estado_periodo === 'Finalizado') {
                        $_SESSION['error'] = "No se puede calificar: el periodo academico asociado esta finalizado.";
                        header('Location: index.php?page=dashboard');
                        exit;
                    }

                    $nuevo_estado = ($nota >= 10) ? 'Aceptada' : 'Rechazada';
                    
                    $stmt = $conexion->prepare("
                        UPDATE solicitud_inscripcion si
                        JOIN seccion s ON si.id_seccion = s.id
                        SET si.nota = :nota, si.estado = :estado
                        WHERE si.id = :id AND s.id_docente = :id_docente
                    ");
                    $stmt->execute([
                        ':nota' => $nota,
                        ':estado' => $nuevo_estado,
                        ':id' => $id_solicitud,
                        ':id_docente' => $_SESSION['id_usuario']
                    ]);
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Calificacion', "Califico solicitud ID $id_solicitud con nota $nota ($nuevo_estado)");
                    $_SESSION['success'] = "Calificacion guardada correctamente.";
                }
            }
            header('Location: index.php?page=dashboard');
            exit;
            break;
        case 'toggle_user_status':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos.']);
                exit;
            }
            $body = json_decode(file_get_contents('php://input'), true);
            $id   = intval($body['id'] ?? 0);
            $csrf = $body['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $csrf) || $id === 0) {
                echo json_encode(['success' => false, 'message' => 'Datos invalidos.']);
                exit;
            }
            $stmt = $conexion->prepare("SELECT estado, rol FROM usuario WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userData === false) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
                exit;
            }
            if ($userData['rol'] === 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'No puedes desactivar a otro Coordinador.']);
                exit;
            }
            $estado = $userData['estado'];
            $nuevo = ($estado === 'Aprobado') ? 'Inactivo' : 'Aprobado';
            $accion = ($nuevo === 'Inactivo') ? 'Desactivo' : 'Reincorporo';
            $modelo = new Usuario($conexion);

            $reassign_to = intval($body['reassign_to'] ?? 0);
            $reassign_msg = '';
            if ($nuevo === 'Inactivo' && $userData['rol'] === 'Docente' && $reassign_to > 0) {
                $stmtNuevo = $conexion->prepare("SELECT id FROM usuario WHERE id = :id AND rol = 'Docente' AND estado = 'Aprobado' LIMIT 1");
                $stmtNuevo->execute([':id' => $reassign_to]);
                if ($stmtNuevo->fetch()) {
                    $stmtSecs = $conexion->prepare("SELECT COUNT(*) FROM seccion WHERE id_docente = :doc");
                    $stmtSecs->execute([':doc' => $id]);
                    $total_secs = (int)$stmtSecs->fetchColumn();
                    if ($total_secs > 0) {
                        $stmtUpd = $conexion->prepare("UPDATE seccion SET id_docente = :nuevo WHERE id_docente = :old");
                        $stmtUpd->execute([':nuevo' => $reassign_to, ':old' => $id]);
                        $reasignadas = $stmtUpd->rowCount();
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Reasignar Materia',
                            "Reasigno $reasignadas secciones del docente ID $id al docente ID $reassign_to");
                        $reassign_msg = " y se reasignaron $reasignadas secciones";
                    }
                }
            }

            $modelo->actualizarEstadoUsuario($id, $nuevo);
            AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Estado Usuario', "$accion al usuario ID $id ($estado → $nuevo)$reassign_msg");
            echo json_encode(['success' => true, 'nuevo_estado' => $nuevo]);
            exit;
            break;
        case 'approve_document':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $csrf_token = $_GET['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    if (isset($_GET['ajax'])) {
                        echo json_encode(['success' => false, 'message' => 'Token CSRF invalido']);
                        exit;
                    }
                    header('Location: index.php?page=approve_registration');
                    exit;
                }

                $id_doc = $_GET['id'] ?? null;
                $estado = $_GET['status'] ?? null;
                if ($id_doc && in_array($estado, ['Aprobado', 'Rechazado'])) {
                    $u_model = new Usuario($conexion);
                    $obs = $_GET['obs'] ?? '';
                    $u_model->actualizarEstadoDocumento($id_doc, $estado, $obs);
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Documento ' . $estado, "Documento ID $id_doc $estado" . ($obs ? " ($obs)" : ""));
                    
                    if (isset($_GET['ajax'])) {
                        echo json_encode(['success' => true, 'status' => $estado]);
                        exit;
                    }
                }
            }
            header('Location: index.php?page=approve_registration');
            exit;
            break;
        // CICLOS ACADEMICOS
        case 'close_cycle':
            if (isset($_SESSION['rol_usuario']) && in_array($_SESSION['rol_usuario'], ['Docente', 'Coordinador'])) {
                $csrf_token = $_GET['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=dashboard');
                    exit;
                }
                $id_materia = intval($_GET['materia'] ?? 0);
                if ($id_materia > 0) {
                    $stmtPer = $conexion->prepare("
                        SELECT COUNT(*) FROM solicitud_inscripcion si
                        JOIN seccion s ON si.id_seccion = s.id
                        LEFT JOIN periodo_academico pa ON si.id_periodo = pa.id
                        WHERE s.id_materia = :mid AND s.id_docente = :doc
                        AND pa.estado = 'Finalizado'
                    ");
                    $stmtPer->execute([':mid' => $id_materia, ':doc' => $_SESSION['id_usuario']]);
                    if ((int)$stmtPer->fetchColumn() > 0) {
                        $_SESSION['error'] = "No se puede cerrar el ciclo: el periodo academico asociado esta finalizado.";
                        header('Location: index.php?page=dashboard');
                        exit;
                    }

                    $stmtPending = $conexion->prepare("
                        SELECT COUNT(*) FROM solicitud_inscripcion si
                        JOIN seccion s ON si.id_seccion = s.id
                        WHERE s.id_materia = :mid AND s.id_docente = :doc
                        AND si.estado = 'Pendiente'
                    ");
                    $stmtPending->execute([':mid' => $id_materia, ':doc' => $_SESSION['id_usuario']]);
                    $pendientes = (int)$stmtPending->fetchColumn();

                    $stmtCheck = $conexion->prepare("
                        SELECT COUNT(*) FROM solicitud_inscripcion si
                        JOIN seccion s ON si.id_seccion = s.id
                        WHERE s.id_materia = :mid AND s.id_docente = :doc
                        AND si.ciclo_cerrado = 0 AND si.nota = 0
                        AND si.estado != 'Pendiente'
                    ");
                    $stmtCheck->execute([':mid' => $id_materia, ':doc' => $_SESSION['id_usuario']]);
                    $sin_nota = (int)$stmtCheck->fetchColumn();

                    if ($pendientes > 0) {
                        $_SESSION['error'] = "No se puede cerrar el ciclo: hay $pendientes estudiante(s) con solicitud Pendiente. Primero aceptelos o rechacelos.";
                        header('Location: index.php?page=dashboard');
                        exit;
                    }
                    if ($sin_nota > 0) {
                        $_SESSION['error'] = "No se puede cerrar el ciclo: hay $sin_nota estudiante(s) sin calificar.";
                        header('Location: index.php?page=dashboard');
                        exit;
                    }
                    $conexion->prepare("UPDATE solicitud_inscripcion si JOIN seccion s ON si.id_seccion = s.id SET si.ciclo_cerrado = 1, si.valido_coordinador = NULL WHERE s.id_materia = :mid AND s.id_docente = :doc AND si.ciclo_cerrado = 0 AND si.estado != 'Pendiente'")
                        ->execute([':mid' => $id_materia, ':doc' => $_SESSION['id_usuario']]);
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Cerrar Ciclo', "Cerro ciclo de materia ID $id_materia");
                    $_SESSION['success'] = "Ciclo cerrado correctamente.";
                }
                header('Location: index.php?page=dashboard');
                exit;
            }
            break;
        case 'validar_ciclo':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $csrf_token = $_GET['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=validar_ciclos');
                    exit;
                }
                $id_est = intval($_GET['id'] ?? 0);
                $resultado = $_GET['resultado'] ?? '';
                if ($id_est > 0 && in_array($resultado, ['validar', 'rechazar'])) {
                    $val = ($resultado === 'validar') ? 1 : 0;
                    $conexion->prepare("UPDATE solicitud_inscripcion SET valido_coordinador = :val, ciclo_cerrado = 0 WHERE id_estudiante = :est AND ciclo_cerrado = 1 AND valido_coordinador IS NULL")
                        ->execute([':val' => $val, ':est' => $id_est]);

                    if ($resultado === 'validar') {
                        $conexion->prepare("UPDATE perfil p JOIN usuario u ON u.id_perfil = p.id SET p.semestre_actual = p.semestre_actual + 1 WHERE u.id = :est")
                            ->execute([':est' => $id_est]);
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Validar Ciclo', "Valido ciclo del estudiante ID $id_est → avanza de semestre");
                    } else {
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Rechazar Ciclo', "Rechazo ciclo del estudiante ID $id_est → ciclo reabierto para correccion");
                    }
                }
                header('Location: index.php?page=validar_ciclos');
                exit;
            }
            break;
        case 'handle_request':
            if (isset($_SESSION['rol_usuario']) && in_array($_SESSION['rol_usuario'], ['Docente', 'Coordinador'])) {
                $csrf_token = $_GET['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=dashboard');
                    exit;
                }
                $id = $_GET['id'] ?? null;
                $estado = $_GET['status'] ?? null;
                if ($id && in_array($estado, ['Aprobada', 'Rechazada'])) {
                    $sentencia = $conexion->prepare(
                        "UPDATE solicitud_inscripcion si
                        JOIN seccion sec ON si.id_seccion = sec.id
                        SET si.estado = :estado
                        WHERE si.id = :id AND sec.id_docente = :id_docente"
                    );
                    $sentencia->execute([':estado' => $estado, ':id' => $id, ':id_docente' => $_SESSION['id_usuario']]);
                    if ($sentencia->rowCount() > 0) {
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Solicitud ' . $estado, "Solicitud de inscripcion ID $id $estado");
                    }
                }
            }
            header('Location: index.php?page=dashboard');
            exit;
            break;
        // BUSQUEDA AJAX
        case 'search_students_ajax':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                exit;
            }
            $busqueda = trim($_GET['q'] ?? '');
            $estudiantes = [];
            if ($busqueda !== '') {
                $like = "%{$busqueda}%";
                $stmt = $conexion->prepare(
                    "SELECT u.id, u.correo, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil
                    FROM usuario u
                    JOIN perfil p ON u.id_perfil = p.id
                    WHERE u.rol = 'Estudiante'
                    AND (p.cedula LIKE :q OR p.nombre LIKE :q OR p.segundo_nombre LIKE :q OR p.apellido LIKE :q OR p.segundo_apellido LIKE :q OR u.correo LIKE :q)
                    ORDER BY p.apellido, p.nombre
                    LIMIT 15"
                );
                $stmt->execute([':q' => $like]);
                $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode(['success' => true, 'data' => $estudiantes]);
            exit;
            break;
        case 'get_student_details_ajax':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                exit;
            }
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID invalido']);
                exit;
            }
            $stmt = $conexion->prepare(
                "SELECT u.id, u.correo, u.estado, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil, p.fecha_carnetizacion
                FROM usuario u 
                JOIN perfil p ON u.id_perfil = p.id
                WHERE u.id = :id AND u.rol = 'Estudiante' LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $est_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$est_data) {
                echo json_encode(['success' => false, 'message' => 'Estudiante no encontrado']);
                exit;
            }

            ob_start();
            include 'app/views/coordinator/partials/student_card.php';
            $html = ob_get_clean();
            
            echo json_encode(['success' => true, 'data' => $est_data, 'html' => $html]);
            exit;
            break;
        case 'search_teachers_ajax':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                exit;
            }
            $busqueda = trim($_GET['q'] ?? '');
            $docentes = [];
            if ($busqueda !== '') {
                $like = "%{$busqueda}%";
                $stmt = $conexion->prepare(
                    "SELECT u.id, u.correo, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil
                    FROM usuario u
                    JOIN perfil p ON u.id_perfil = p.id
                    WHERE u.rol = 'Docente'
                    AND (p.cedula LIKE :q OR p.nombre LIKE :q OR p.segundo_nombre LIKE :q OR p.apellido LIKE :q OR p.segundo_apellido LIKE :q OR u.correo LIKE :q)
                    ORDER BY p.apellido, p.nombre
                    LIMIT 15"
                );
                $stmt->execute([':q' => $like]);
                $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode(['success' => true, 'data' => $docentes]);
            exit;
            break;
        case 'get_teacher_details_ajax':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                exit;
            }
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID invalido']);
                exit;
            }
            $stmt = $conexion->prepare(
                "SELECT u.id, u.correo, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento, p.foto_perfil, p.fecha_carnetizacion
                FROM usuario u
                JOIN perfil p ON u.id_perfil = p.id
                WHERE u.id = :id AND u.rol = 'Docente' LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $est_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$est_data) {
                echo json_encode(['success' => false, 'message' => 'Docente no encontrado']);
                exit;
            }

            ob_start();
            include 'app/views/coordinator/partials/teacher_card.php';
            $html = ob_get_clean();

            echo json_encode(['success' => true, 'data' => $est_data, 'html' => $html]);
            exit;
            break;
        // PERFIL DE USUARIO
        case 'update_own_profile':
            if (!isset($_SESSION['id_usuario'])) {
                $_SESSION['error'] = 'Sin sesion.';
                header('Location: index.php?page=login');
                exit;
            }
            $csrf_in = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $csrf_in)) {
                $_SESSION['error'] = 'Token invalido. Intente de nuevo.';
                header('Location: index.php?page=mi_perfil');
                exit;
            }
            $modeloUsuario = new Usuario($conexion);
            $user_id = $_SESSION['id_usuario'];

            $stmt = $conexion->prepare("SELECT id_perfil FROM usuario WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $user_id]);
            $id_perfil = $stmt->fetchColumn();
            if (!$id_perfil) {
                $_SESSION['error'] = 'Perfil no encontrado.';
                header('Location: index.php?page=mi_perfil');
                exit;
            }

            $errores = [];

            $datos = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'segundo_nombre' => trim($_POST['segundo_nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'segundo_apellido' => trim($_POST['segundo_apellido'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
            ];
            if (empty($datos['nombre']) || empty($datos['apellido'])) {
                $errores[] = 'Nombre y apellido son obligatorios.';
            }

            if (!empty($errores)) {
                $_SESSION['error'] = implode(' ', $errores);
                header('Location: index.php?page=mi_perfil');
                exit;
            }

            $modeloUsuario->actualizarMiPerfil($id_perfil, $datos);

            if (!empty($_FILES['foto_perfil']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $_SESSION['error'] = 'Formato de imagen no permitido. Use JPG, PNG o WEBP.';
                    header('Location: index.php?page=mi_perfil');
                    exit;
                }
                $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $dir = 'public/uploads/profiles/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $stmtOld = $conexion->prepare("SELECT foto_perfil FROM perfil WHERE id = :id LIMIT 1");
                $stmtOld->execute([':id' => $id_perfil]);
                $old = $stmtOld->fetchColumn();
                if ($old && file_exists($dir . $old) && $old !== 'default.svg') @unlink($dir . $old);
                move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $dir . $filename);
                $stmtUp = $conexion->prepare("UPDATE perfil SET foto_perfil = :pic WHERE id = :id");
                $stmtUp->execute([':pic' => $filename, ':id' => $id_perfil]);
            }

            $msg_extra = '';
            $pass_old = $_POST['pass_old'] ?? '';
            $pass_new = $_POST['pass_new'] ?? '';
            $pass_confirm = $_POST['pass_confirm'] ?? '';
            if (!empty($pass_old) || !empty($pass_new) || !empty($pass_confirm)) {
                if (empty($pass_old) || empty($pass_new) || empty($pass_confirm)) {
                    $_SESSION['error'] = 'Debe llenar todos los campos de contrasena.';
                    header('Location: index.php?page=mi_perfil');
                    exit;
                }
                if ($pass_new !== $pass_confirm) {
                    $_SESSION['error'] = 'Las contrasenas nuevas no coinciden.';
                    header('Location: index.php?page=mi_perfil');
                    exit;
                }
                if (strlen($pass_new) < 8) {
                    $_SESSION['error'] = 'La contrasena debe tener al menos 8 caracteres.';
                    header('Location: index.php?page=mi_perfil');
                    exit;
                }
                $stmtPass = $conexion->prepare("SELECT clave FROM usuario WHERE id = :id LIMIT 1");
                $stmtPass->execute([':id' => $user_id]);
                $hashActual = $stmtPass->fetchColumn();
                if (!password_verify($pass_old, $hashActual)) {
                    $_SESSION['error'] = 'La contrasena actual es incorrecta.';
                    header('Location: index.php?page=mi_perfil');
                    exit;
                }
                $modeloUsuario->actualizarClave($user_id, $pass_new);
                $msg_extra = ' y contrasena actualizada';
            }

            $_SESSION['nombre_usuario'] = $datos['nombre'] . ' ' . $datos['apellido'];
            $_SESSION['nombre_completo'] = trim("{$datos['nombre']} {$datos['segundo_nombre']} {$datos['apellido']} {$datos['segundo_apellido']}");

            AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Actualizar Perfil', "Actualizo su propio perfil$msg_extra");
            $_SESSION['success'] = 'Perfil actualizado correctamente' . $msg_extra . '.';
            header('Location: index.php?page=mi_perfil');
            exit;
            break;
        case 'bitacora_live':
            header('Content-Type: application/json');
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                echo json_encode(['html' => '', 'count' => 0]);
                exit;
            }
            $registros = AuditHelper::obtenerUltimos($conexion, 50);
            ob_start();
            foreach ($registros as $r): ?>
                <tr>
                    <td style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:10px 12px 10px 8px;"><?php echo date('d/m/Y H:i', strtotime($r['fecha_hora'])); ?></td>
                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 6px;"><span class="badge bg-secondary"><?php echo htmlspecialchars($r['rol'] ?? '-'); ?></span></td>
                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 18px 10px 6px;" title="<?php echo htmlspecialchars($r['correo'] ?? 'Sistema'); ?>"><?php echo htmlspecialchars($r['correo'] ?? 'Sistema'); ?></td>
                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 6px;"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($r['accion']); ?></span></td>
                    <td style="white-space:normal;word-break:break-word;padding:10px 6px;"><?php echo htmlspecialchars($r['detalle'] ?? '-'); ?></td>
                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 6px;"><code><?php echo htmlspecialchars($r['direccion_ip']); ?></code></td>
                </tr>
            <?php endforeach;
            $html = ob_get_clean();
            echo json_encode(['html' => $html, 'count' => count($registros)]);
            exit;
            break;
        // CONSTANCIAS PDF
        case 'constancia_estudio':
            if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Estudiante') {
                header('Location: index.php?page=login');
                exit;
            }
            $csrf_token = $_GET['csrf_token'] ?? '';
            if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                header('Location: index.php?page=dashboard');
                exit;
            }
            $pdf = new ReportesPDF('P', 'mm', 'A4');
            $contenido = $pdf->constanciaEstudio($conexion, $_SESSION['id_usuario']);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="constancia_estudio.pdf"');
            echo $contenido;
            exit;
            break;

        case 'constancia_inscripcion':
            if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Estudiante') {
                header('Location: index.php?page=login');
                exit;
            }
            $csrf_token = $_GET['csrf_token'] ?? '';
            if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                header('Location: index.php?page=dashboard');
                exit;
            }
            $pdf = new ReportesPDF('P', 'mm', 'A4');
            $contenido = $pdf->constanciaInscripcion($conexion, $_SESSION['id_usuario']);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="constancia_inscripcion.pdf"');
            echo $contenido;
            exit;
            break;

        // CRONOGRAMA ACADEMICO
        case 'add_crono_evento':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=cronograma');
                    exit;
                }
                $anio = intval($_POST['anio'] ?? 0);
                $categoria = trim($_POST['categoria'] ?? '');
                $periodo = trim($_POST['periodo'] ?? '');
                if ($periodo === '') $periodo = trim($_POST['periodo_select'] ?? '');
                if ($periodo === '__otro__') $periodo = '';
                $descripcion = trim($_POST['descripcion'] ?? '');
                if ($descripcion === '') $descripcion = trim($_POST['descripcion_select'] ?? '');
                if ($descripcion === '__otro__') $descripcion = '';
                $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
                $fecha_fin = trim($_POST['fecha_fin'] ?? '');
                $id_periodo = !empty($_POST['id_periodo']) ? intval($_POST['id_periodo']) : null;
                if ($anio > 0 && $categoria !== '' && $periodo !== '' && $descripcion !== '' && $fecha_inicio !== '' && $fecha_fin !== '') {
                    $stmt = $conexion->prepare("INSERT INTO cronograma_evento (anio, categoria, periodo, descripcion, fecha_inicio, fecha_fin, id_periodo) VALUES (:anio, :cat, :per, :desc, :fi, :ff, :idp)");
                    $stmt->execute([':anio' => $anio, ':cat' => $categoria, ':per' => $periodo, ':desc' => $descripcion, ':fi' => $fecha_inicio, ':ff' => $fecha_fin, ':idp' => $id_periodo]);
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Cronograma', "Agrego evento: $descripcion ($categoria $periodo $anio)");
                    $_SESSION['success'] = "Evento agregado correctamente.";
                }
            }
            header('Location: index.php?page=cronograma');
            exit;
            break;
        case 'edit_crono_evento':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=cronograma');
                    exit;
                }
                $id = intval($_POST['id'] ?? 0);
                $periodo = trim($_POST['periodo'] ?? '');
                if ($periodo === '') $periodo = trim($_POST['edit_periodo_select'] ?? '');
                if ($periodo === '__otro__') $periodo = '';
                $descripcion = trim($_POST['descripcion'] ?? '');
                if ($descripcion === '') $descripcion = trim($_POST['edit_descripcion_select'] ?? '');
                if ($descripcion === '__otro__') $descripcion = '';
                $categoria = trim($_POST['categoria'] ?? '');
                $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
                $fecha_fin = trim($_POST['fecha_fin'] ?? '');
                $id_periodo = !empty($_POST['id_periodo']) ? intval($_POST['id_periodo']) : null;
                if ($id > 0 && $periodo !== '' && $descripcion !== '' && $categoria !== '' && $fecha_inicio !== '' && $fecha_fin !== '') {
                    $stmt = $conexion->prepare("UPDATE cronograma_evento SET categoria = :cat, periodo = :per, descripcion = :desc, fecha_inicio = :fi, fecha_fin = :ff, id_periodo = :idp WHERE id = :id");
                    $stmt->execute([':cat' => $categoria, ':per' => $periodo, ':desc' => $descripcion, ':fi' => $fecha_inicio, ':ff' => $fecha_fin, ':idp' => $id_periodo, ':id' => $id]);
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Cronograma', "Edito evento ID $id");
                    $_SESSION['success'] = "Evento actualizado correctamente.";
                }
            }
            header('Location: index.php?page=cronograma');
            exit;
            break;
        case 'delete_crono_evento':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $id = intval($_GET['id'] ?? 0);
                $csrf_token = $_GET['csrf_token'] ?? '';
                if ($id > 0 && !empty($csrf_token) && hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    $stmt = $conexion->prepare("DELETE FROM cronograma_evento WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Cronograma', "Elimino evento ID $id");
                    $_SESSION['success'] = "Evento eliminado correctamente.";
                }
            }
            header('Location: index.php?page=cronograma');
            exit;
            break;
        case 'copy_cronograma_anio':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $origen = intval($_GET['origen'] ?? 0);
                $destino = intval($_GET['destino'] ?? 0);
                $csrf_token = $_GET['csrf_token'] ?? '';
                if ($origen > 0 && $destino > 0 && $origen !== $destino && !empty($csrf_token) && hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    $diff = $destino - $origen;
                    $stmt = $conexion->prepare("SELECT * FROM cronograma_evento WHERE anio = :anio ORDER BY id");
                    $stmt->execute([':anio' => $origen]);
                    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $insertados = 0;
                    foreach ($eventos as $e) {
                        $nueva_fi = date('Y-m-d', strtotime($e['fecha_inicio'] . " +$diff years"));
                        $nueva_ff = date('Y-m-d', strtotime($e['fecha_fin'] . " +$diff years"));
                        $nuevo_periodo = preg_replace('/\b' . $origen . '\b/', (string)$destino, $e['periodo']);
                        $check = $conexion->prepare("SELECT id FROM cronograma_evento WHERE anio = :anio AND categoria = :cat AND periodo = :per AND descripcion = :desc LIMIT 1");
                        $check->execute([':anio' => $destino, ':cat' => $e['categoria'], ':per' => $nuevo_periodo, ':desc' => $e['descripcion']]);
                        if (!$check->fetch()) {
                            $ins = $conexion->prepare("INSERT INTO cronograma_evento (anio, categoria, periodo, descripcion, fecha_inicio, fecha_fin, id_periodo) VALUES (:anio, :cat, :per, :desc, :fi, :ff, :idp)");
                            $ins->execute([':anio' => $destino, ':cat' => $e['categoria'], ':per' => $nuevo_periodo, ':desc' => $e['descripcion'], ':fi' => $nueva_fi, ':ff' => $nueva_ff, ':idp' => $e['id_periodo'] ?? null]);
                            $insertados++;
                        }
                    }
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Cronograma', "Copio cronograma $origen → $destino ($insertados eventos)");
                    $_SESSION['success'] = "Cronograma de $origen copiado a $destino. Se insertaron $insertados eventos nuevos.";
                }
            }
            header('Location: index.php?page=cronograma');
            exit;
            break;
        case 'auto_generar_anios':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $base = intval($_GET['base'] ?? 2026);
                $hasta = intval($_GET['hasta'] ?? 0);
                $csrf_token = $_GET['csrf_token'] ?? '';
                if ($base > 0 && $hasta > $base && !empty($csrf_token) && hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    $stmt = $conexion->prepare("SELECT * FROM cronograma_evento WHERE anio = :anio ORDER BY id");
                    $stmt->execute([':anio' => $base]);
                    $base_eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($base_eventos)) {
                        $_SESSION['error'] = "No hay eventos en el ano base $base.";
                    } else {
                        $total = 0;
                        for ($destino = $base + 1; $destino <= $hasta; $destino++) {
                            $diff = $destino - $base;
                            $insertados = 0;
                            foreach ($base_eventos as $e) {
                                $nueva_fi = date('Y-m-d', strtotime($e['fecha_inicio'] . " +$diff years"));
                                $nueva_ff = date('Y-m-d', strtotime($e['fecha_fin'] . " +$diff years"));
                                $nuevo_periodo = preg_replace('/\b' . $base . '\b/', (string)$destino, $e['periodo']);
                                $check = $conexion->prepare("SELECT id FROM cronograma_evento WHERE anio = :anio AND categoria = :cat AND periodo = :per AND descripcion = :desc LIMIT 1");
                                $check->execute([':anio' => $destino, ':cat' => $e['categoria'], ':per' => $nuevo_periodo, ':desc' => $e['descripcion']]);
                                if (!$check->fetch()) {
                                    $ins = $conexion->prepare("INSERT INTO cronograma_evento (anio, categoria, periodo, descripcion, fecha_inicio, fecha_fin, id_periodo) VALUES (:anio, :cat, :per, :desc, :fi, :ff, :idp)");
                                    $ins->execute([':anio' => $destino, ':cat' => $e['categoria'], ':per' => $nuevo_periodo, ':desc' => $e['descripcion'], ':fi' => $nueva_fi, ':ff' => $nueva_ff, ':idp' => $e['id_periodo'] ?? null]);
                                    $insertados++;
                                }
                            }
                            $total += $insertados;
                        }
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Cronograma', "Genero cronogramas $base → $hasta ($total eventos)");
                        $_SESSION['success'] = "Cronogramas generados del $base al $hasta ($total eventos nuevos). Puede revisarlos y editarlos.";
                    }
                }
            }
            header('Location: index.php?page=cronograma');
            exit;
            break;
        case 'upload_cronograma_file':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=cronograma');
                    exit;
                }
                $anio = intval($_POST['anio_file'] ?? 0);
                if ($anio > 0 && isset($_FILES['cronograma_file']) && $_FILES['cronograma_file']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['cronograma_file']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['pdf', 'xls', 'xlsx', 'jpg', 'png'])) {
                        $_SESSION['error'] = 'Formato no permitido. Use PDF, Excel (xls/xlsx) o imagen.';
                    } else {
                        $dir = URLROOT . '/assets/documentos/cronogramas/';
                        $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . '/PROYECTO SICEU/UNEFA/' . $dir;
                        if (!is_dir($ruta_fisica)) {
                            mkdir($ruta_fisica, 0755, true);
                        }
                        $nombre_archivo = "calendario_academico_{$anio}." . $ext;
                        $destino = $ruta_fisica . $nombre_archivo;
                        move_uploaded_file($_FILES['cronograma_file']['tmp_name'], $destino);
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Cronograma', "Subio archivo calendario $anio: $nombre_archivo");
                        $_SESSION['success'] = "Archivo del calendario $anio subido correctamente.";
                    }
                } else {
                    $_SESSION['error'] = 'Error al subir el archivo. Seleccione un ano y un archivo valido.';
                }
            }
            header('Location: index.php?page=cronograma');
            exit;
            break;
        // PERIODOS ACADEMICOS
        case 'add_periodo':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $csrf_token = $_POST['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    $_SESSION['error'] = 'Token CSRF invalido.';
                    header('Location: index.php?page=gestion_periodos');
                    exit;
                }
                $nombre = trim($_POST['nombre'] ?? '');
                $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
                $fecha_fin = trim($_POST['fecha_fin'] ?? '');
                if ($nombre === '' || $fecha_inicio === '') {
                    $_SESSION['error'] = 'El nombre y la fecha de inicio son obligatorios.';
                    header('Location: index.php?page=gestion_periodos');
                    exit;
                }
                $stmt = $conexion->prepare("INSERT INTO periodo_academico (nombre, fecha_inicio, fecha_fin) VALUES (:nom, :fi, :ff)");
                $stmt->execute([':nom' => $nombre, ':fi' => $fecha_inicio, ':ff' => $fecha_fin ?: null]);
                AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Crear Periodo', "Creo periodo academico: $nombre");
                $_SESSION['success'] = "Periodo academico '$nombre' creado correctamente.";
            }
            header('Location: index.php?page=gestion_periodos');
            exit;
            break;
        case 'activate_periodo':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $id = intval($_GET['id'] ?? 0);
                $csrf_token = $_GET['csrf_token'] ?? '';
                if ($id > 0 && !empty($csrf_token) && hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    $conexion->prepare("UPDATE periodo_academico SET estado = 'Planificado' WHERE estado = 'Activo'")->execute();
                    $conexion->prepare("UPDATE periodo_academico SET estado = 'Activo' WHERE id = :id")->execute([':id' => $id]);
                    $stmtN = $conexion->prepare("SELECT nombre FROM periodo_academico WHERE id = :id");
                    $stmtN->execute([':id' => $id]);
                    $nombre_periodo = $stmtN->fetchColumn();
                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Iniciar Periodo', "Inicio periodo academico ID $id ($nombre_periodo)");
                    $_SESSION['success'] = "Periodo «" . htmlspecialchars($nombre_periodo ?: '') . "» iniciado correctamente.";
                    $_SESSION['success_extra'] = "Puedes copiar eventos del cronograma del ano anterior desde la pagina de <a href='index.php?page=cronograma'>Cronograma academico</a>.";
                }
            }
            header('Location: index.php?page=gestion_periodos');
            exit;
            break;
        case 'finalize_periodo':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $id = intval($_GET['id'] ?? 0);
                $csrf_token = $_GET['csrf_token'] ?? '';
                if ($id > 0 && !empty($csrf_token) && hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    $stmt = $conexion->prepare("SELECT estado FROM periodo_academico WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $estado = $stmt->fetchColumn();
                    if ($estado === false) {
                        $_SESSION['error'] = 'Periodo no encontrado.';
                    } elseif ($estado !== 'Activo') {
                        $_SESSION['error'] = 'Solo se puede finalizar un periodo activo.';
                    } else {
                        $conexion->prepare("UPDATE periodo_academico SET estado = 'Finalizado', fecha_fin = CURDATE() WHERE id = :id AND estado = 'Activo'")->execute([':id' => $id]);
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Finalizar Periodo', "Finalizo periodo academico ID $id");
                        $_SESSION['success'] = "Periodo finalizado correctamente.";
                    }
                }
            }
            header('Location: index.php?page=gestion_periodos');
            exit;
            break;
        case 'delete_periodo':
            if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Coordinador') {
                $id = intval($_GET['id'] ?? 0);
                $csrf_token = $_GET['csrf_token'] ?? '';
                if ($id > 0 && !empty($csrf_token) && hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    $stmt = $conexion->prepare("SELECT estado FROM periodo_academico WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $estado = $stmt->fetchColumn();
                    if ($estado === false) {
                        $_SESSION['error'] = 'Periodo no encontrado.';
                    } elseif ($estado === 'Activo') {
                        $_SESSION['error'] = 'No se puede eliminar el periodo activo. Finalicelo primero.';
                    } else {
                        $stmt = $conexion->prepare("DELETE FROM periodo_academico WHERE id = :id AND estado != 'Activo'");
                        $stmt->execute([':id' => $id]);
                        AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Eliminar Periodo', "Elimino periodo academico ID $id");
                        $_SESSION['success'] = "Periodo eliminado correctamente.";
                    }
                }
            }
            header('Location: index.php?page=gestion_periodos');
            exit;
            break;
        case 'download_teacher_report':
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['id_usuario']) && in_array($_SESSION['rol_usuario'], ['Docente', 'Coordinador'])) {
                $csrf_token = $_GET['csrf_token'] ?? '';
                if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                    header('Location: index.php?page=dashboard');
                    exit;
                }
                $id_materia = intval($_GET['materia'] ?? 0);
                $id_periodo = intval($_GET['periodo'] ?? 0);
                $id_docente = $_SESSION['id_usuario'];
                if ($id_materia > 0) {
                    $stmtDoc = $conexion->prepare("SELECT p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula FROM usuario u JOIN perfil p ON u.id_perfil = p.id WHERE u.id = :id");
                    $stmtDoc->execute([':id' => $id_docente]);
                    $docente = $stmtDoc->fetch(PDO::FETCH_ASSOC);
                    $nombre_docente = trim(($docente['nombre'] ?? '') . ' ' . ($docente['segundo_nombre'] ?? '') . ' ' . ($docente['apellido'] ?? '') . ' ' . ($docente['segundo_apellido'] ?? ''));

                    $stmtMat = $conexion->prepare("SELECT nombre_materia, semestre FROM materia WHERE id = :id");
                    $stmtMat->execute([':id' => $id_materia]);
                    $materia = $stmtMat->fetch(PDO::FETCH_ASSOC);

                    $periodo_nombre = '';
                    if ($id_periodo > 0) {
                        $periodo_nombre = $conexion->prepare("SELECT nombre FROM periodo_academico WHERE id = :id");
                        $periodo_nombre->execute([':id' => $id_periodo]);
                        $periodo_nombre = $periodo_nombre->fetchColumn();
                    }

                    $stmtEst = $conexion->prepare("
                        SELECT p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula,
                            si.nota, si.estado, si.ciclo_cerrado
                        FROM solicitud_inscripcion si
                        JOIN usuario u ON si.id_estudiante = u.id
                        JOIN perfil p ON u.id_perfil = p.id
                        JOIN seccion s ON si.id_seccion = s.id
                        WHERE s.id_materia = :mat AND s.id_docente = :doc
                        AND si.estado != 'Pendiente'
                        AND (:per = 0 OR si.id_periodo = :per2)
                        ORDER BY p.apellido, p.nombre
                    ");
                    $stmtEst->execute([':mat' => $id_materia, ':doc' => $id_docente, ':per' => $id_periodo, ':per2' => $id_periodo]);
                    $estudiantes = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

                    require_once 'libs/fpdf.php';
                    $pdf = new FPDF('P', 'mm', 'A4');
                    $pdf->AddPage();
                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 10, 'Reporte de Notas', 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->Cell(0, 6, 'Docente: ' . htmlspecialchars($nombre_docente), 0, 1);
                    $pdf->Cell(0, 6, 'Materia: ' . htmlspecialchars($materia['nombre_materia'] ?? ''), 0, 1);
                    if ($periodo_nombre) {
                        $pdf->Cell(0, 6, 'Periodo: ' . htmlspecialchars($periodo_nombre), 0, 1);
                    }
                    $pdf->Ln(5);

                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->Cell(10, 7, '#', 1, 0, 'C');
                    $pdf->Cell(60, 7, 'Apellidos', 1);
                    $pdf->Cell(50, 7, 'Nombres', 1);
                    $pdf->Cell(25, 7, 'Cedula', 1, 0, 'C');
                    $pdf->Cell(20, 7, 'Nota', 1, 0, 'C');
                    $pdf->Cell(25, 7, 'Estado', 1, 1, 'C');

                    $pdf->SetFont('Arial', '', 9);
                    $i = 1;
                    foreach ($estudiantes as $e) {
                        $apellidos = trim(($e['apellido'] ?? '') . ' ' . ($e['segundo_apellido'] ?? ''));
                        $nombres = trim(($e['nombre'] ?? '') . ' ' . ($e['segundo_nombre'] ?? ''));
                        $nota_val = $e['nota'] > 0 ? $e['nota'] : '-';
                        $estado_val = '';
                        if ($e['nota'] > 0) {
                            $estado_val = $e['nota'] >= 10 ? 'Aprobado' : 'Reprobado';
                        } else {
                            $estado_val = 'Cursando';
                        }
                        $pdf->Cell(10, 6, $i++, 1, 0, 'C');
                        $pdf->Cell(60, 6, htmlspecialchars($apellidos), 1);
                        $pdf->Cell(50, 6, htmlspecialchars($nombres), 1);
                        $pdf->Cell(25, 6, htmlspecialchars($e['cedula']), 1, 0, 'C');
                        $pdf->Cell(20, 6, $nota_val, 1, 0, 'C');
                        $pdf->Cell(25, 6, $estado_val, 1, 1, 'C');
                    }

                    AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Reporte PDF', "Descargo reporte de notas (materia ID $id_materia)");
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="reporte_notas_' . $id_materia . '.pdf"');
                    echo $pdf->Output('S');
                    exit;
                }
            }
            header('Location: index.php?page=dashboard');
            exit;
            break;
        case 'generate_pdf':
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
                header('Location: index.php?page=login');
                exit;
            }
            $tipo = $_GET['tipo'] ?? '';
            $preview = isset($_GET['preview']);
            $csrf_token = $_GET['csrf_token'] ?? '';
            if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                header('Location: index.php?page=auditoria');
                exit;
            }
            $nombres = ['estudiantes'=>'reporte_estudiantes','docentes'=>'reporte_docentes','bitacora'=>'bitacora_actividad'];
            $metodos = ['estudiantes'=>'reporteEstudiantes','docentes'=>'reporteDocentes','bitacora'=>'reporteBitacora'];
            if (!isset($nombres[$tipo])) {
                header('Location: index.php?page=auditoria');
                exit;
            }
            $pdf = new ReportesPDF('L', 'mm', 'A4');
            $pdf->SetTitle($nombres[$tipo]);
            $contenido = $pdf->{$metodos[$tipo]}($conexion);
            AuditHelper::registrar($conexion, $_SESSION['id_usuario'], 'Reporte PDF', "Genero reporte de $tipo");
            $dispo = $preview ? 'inline' : 'attachment';
            header('Content-Type: application/pdf');
            header("Content-Disposition: {$dispo}; filename=\"{$nombres[$tipo]}.pdf\"");
            echo $contenido;
            exit;
            break;
    }
}

// APROBACION DE REGISTROS
if (isset($_GET['approve_id']) || isset($_GET['reject_id'])) {
    require_once 'app/models/Usuario.php';
    $modeloUsuario = new Usuario($conexion);
    $csrf_token = $_GET['csrf_token'] ?? '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        header('Location: index.php?page=approve_registration');
        exit;
    }
    if (isset($_GET['approve_id'])) {
        $id_est = intval($_GET['approve_id']);
        $modeloUsuario->actualizarEstadoUsuario($id_est, 'Aprobado');
        header('Location: index.php?page=approve_registration&msg=approved');
        exit;
    }
    if (isset($_GET['reject_id'])) {
        $modeloUsuario->actualizarEstadoUsuario($_GET['reject_id'], 'Rechazado');
        header('Location: index.php?page=approve_registration&msg=rejected');
        exit;
    }
}

// PAGINA DE PLANILLA
if (isset($_GET['page']) && $_GET['page'] === 'planilla') {
    if (!isset($_SESSION['id_usuario_temp']) && !isset($_SESSION['id_usuario'])) {
        header('Location: index.php?page=login');
        exit;
    }
    if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] !== 'Estudiante') {
        header('Location: index.php?page=login');
        exit;
    }
    require_once 'app/views/auth/planilla.view.php';
    exit;
}

// ENRUTADOR DE PAGINAS
$pagina = $_GET['page'] ?? 'home';

require_once 'app/views/layout/header.php';

echo '<div class="unefa-zoom-wrapper flex-grow-1 d-flex flex-column">';

switch ($pagina) {
    // AUTENTICACION
    case 'login':
        require_once 'app/views/auth/login.php';
        break;
    case 'register':
        require_once 'app/views/auth/register.php';
        break;
    case 'reset_password':
        require_once 'app/views/auth/reset_password.php';
        break;
    case 'upload_docs':
        require_once 'app/views/auth/upload_docs.php';
        break;
    // COORDINADOR
    case 'approve_registration':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/dashboard.php';
        break;
    case 'gestion_docentes':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/teacher_management.php';
        break;
    case 'dashboard':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/dashboard/index.php';
        break;
    // ESTUDIANTE
    case 'enrollment':
        if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Estudiante') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/dashboard/enrollment.view.php';
        break;
    case 'contenidos':
        header('Location: https://unefazuliaingsistemas.wordpress.com/la-ingenieria-de-sistemas/contenidos-programaticos/');
        exit;
        break;
    case 'prelacion':
        require_once 'app/views/home/prelacion.php';
        break;
    case 'carnetizacion':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/carnetizacion.php';
        break;
    case 'gestion_carnets':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/carnet_simulator.php';
        break;
    case 'documentos_usuarios':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/documentos_usuarios.php';
        break;
    case 'validar_ciclos':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/validar_ciclos.php';
        break;
    case 'gestion_periodos':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/gestion_periodos.php';
        break;
    case 'cronograma':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/coordinator/cronograma.php';
        break;
    // CARNET Y PERFIL
    case 'mi_qr':
        if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Estudiante') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/student/qr.php';
        break;
    case 'record':
        if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Estudiante') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/student/record.php';
        break;
    case 'mi_carnet':
        if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Estudiante') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/student/carnet.php';
        break;
    case 'mi_carnet_docente':
        if (!isset($_SESSION['id_usuario']) || $_SESSION['rol_usuario'] !== 'Docente') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/teacher/carnet.php';
        break;
    case 'auditoria':
        if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/reports/auditoria.php';
        break;
    case 'mi_perfil':
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'app/views/profile/mi_perfil.php';
        break;
    case 'home':
    default:
        $heroes = require_once 'app/views/home/heroes.php';
        $id_heroe = $_GET['id'] ?? null;
        $heroe = $id_heroe && isset($heroes[$id_heroe]) ? $heroes[$id_heroe] : null;
        if ($heroe) {
            require_once 'app/views/home/heroe_info.php';
        } else {
            require_once 'app/views/home/index.php';
        }
        break;
}

echo '</div>';

echo '</main>';

if (!isset($_GET['page']) || $_GET['page'] === 'home'):
?>
    <div class="social-sidebar">
        <a href="https://x.com/Unefa_VEN?t=FhK2uslLRmCrIa9sjQIEEA&s=09" target="_blank" class="social-link social-x" title="X">
            <img src="<?php echo URLROOT; ?>/assets/img/redes/X-Twitter.webp" alt="X">
        </a>
        <a href="https://www.youtube.com/channel/UCU1YFZgV-ENQkfHRspsK9nA" target="_blank" class="social-link social-yt" title="YouTube">
            <img src="<?php echo URLROOT; ?>/assets/img/redes/Youtube.webp" alt="YouTube">
        </a>
        <a href="https://www.instagram.com/unefa_ve?igsh=MXJvcjFkMXJ5Z3NzMg%3D%3D" target="_blank" class="social-link social-ig" title="Instagram">
            <img src="<?php echo URLROOT; ?>/assets/img/redes/Instagram.webp" alt="Instagram">
        </a>
        <a href="https://www.facebook.com/share/1BKuAut1dg/" target="_blank" class="social-link social-fb" title="Facebook">
            <img src="<?php echo URLROOT; ?>/assets/img/redes/Facebook.webp" alt="Facebook">
        </a>
        <a href="https://www.tiktok.com/@unefa_ve?_t=8iwcWCLFEAA&_r=1" target="_blank" class="social-link social-tk" title="TikTok">
            <img src="<?php echo URLROOT; ?>/assets/img/redes/Tiktok.webp" alt="TikTok">
        </a>
    </div>
<?php endif;

require_once 'app/views/layout/footer.php';
