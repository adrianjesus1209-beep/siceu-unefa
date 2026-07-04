<?php
// CONTROLADOR DE AUTENTICACION
class AuthController {
    private $modeloUsuario;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        require_once 'app/models/Usuario.php';
        $this->modeloUsuario = new Usuario($db);
    }

    public function ingresar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $token_enviado = $_POST['csrf_token'] ?? '';
            if (empty($token_enviado) || !hash_equals($_SESSION['csrf_token'], $token_enviado)) {
                $_SESSION['error'] = "Error de seguridad: Token CSRF invalido.";
                header('Location: index.php?page=login');
                exit;
            }
            $correo = $_POST['correo'] ?? '';
            $clave = $_POST['clave'] ?? '';
            $rol_seleccionado = $_POST['rol'] ?? '';

            $usuario = $this->modeloUsuario->iniciarSesion($correo, $clave);
            if ($usuario) {
                if ($usuario['rol'] !== $rol_seleccionado) {
                    registrarIntentoFallido($this->db, 'login', $correo);
                    $_SESSION['error'] = "Acceso denegado: El perfil seleccionado no coincide con el tipo de cuenta registrada.";
                    header('Location: index.php?page=login');
                    exit;
                }

                if ($usuario['estado'] === 'Inactivo') {
                    registrarIntentoFallido($this->db, 'login', $correo);
                    $_SESSION['error'] = "Su cuenta ha sido desactivada. Contacte a la coordinacion.";
                    header('Location: index.php?page=login');
                    exit;
                }

                if ($usuario['rol'] === 'Estudiante' && $usuario['estado'] !== 'Aprobado') {
                    $_SESSION['id_usuario_temp'] = $usuario['id'];
                    limpiarIntentosFallidos($this->db, 'login');
                    
                    $documentos = $this->modeloUsuario->obtenerDocumentosUsuario($usuario['id']);
                    $tiene_rechazos = false;
                    foreach ($documentos as $doc) {
                        if ($doc['estado'] === 'Rechazado') {
                            $tiene_rechazos = true;
                            break;
                        }
                    }

                    if ($usuario['estado'] === 'Rechazado' || $tiene_rechazos) {
                        $_SESSION['error'] = "Su registro requiere atencion. Por favor, revise los documentos marcados y vuelva a subirlos.";
                    } else {
                        if (!empty($documentos)) {
                            $_SESSION['error'] = "Su registro esta pendiente de aprobacion. Sus documentos ya estan en revision.";
                            header('Location: index.php?page=login');
                            exit;
                        }
                    }
                    header('Location: index.php?page=upload_docs');
                    exit;
                }

                limpiarIntentosFallidos($this->db, 'login');
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                $_SESSION['nombre_completo'] = trim("{$usuario['nombre']} {$usuario['segundo_nombre']} {$usuario['apellido']} {$usuario['segundo_apellido']}");
                $_SESSION['tipo_doc'] = $usuario['tipo_documento'];
                $_SESSION['rol_usuario'] = $usuario['rol'];
                if ($usuario['rol'] === 'Estudiante' && $usuario['estado'] === 'Aprobado') {
                    $_SESSION['flash_aprobado'] = true;
                }

                switch ($usuario['rol']) {
                    case 'Coordinador':
                        header('Location: index.php?page=dashboard');
                        break;
                    case 'Docente':
                        header('Location: index.php?page=dashboard&rol=Docente');
                        break;
                    case 'Estudiante':
                    default:
                        header('Location: index.php?page=dashboard&rol=Estudiante');
                        break;
                }
                exit;
            } else {
                registrarIntentoFallido($this->db, 'login', $correo);
                $_SESSION['error'] = "Credenciales incorrectas. Verifique su correo y clave.";
                header('Location: index.php?page=login');
                exit;
            }
        }
    }

    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $token_enviado = $_POST['csrf_token'] ?? '';
            if (empty($token_enviado) || !hash_equals($_SESSION['csrf_token'], $token_enviado)) {
                $_SESSION['error'] = "Error de seguridad: Token CSRF invalido.";
                header('Location: index.php?page=register');
                exit;
            }
            $rol = 'Estudiante';

            $clave = $_POST['clave'] ?? '';
            if (strlen($clave) < 8) {
                $_SESSION['error'] = "La contrasena debe tener al menos 8 caracteres.";
                header('Location: index.php?page=register');
                exit;
            }

            $cedula = $_POST['cedula'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            if (!preg_match('/^[0-9]+$/', $cedula) || !preg_match('/^[0-9]+$/', $telefono)) {
                $_SESSION['error'] = "La cedula y el telefono deben contener unicamente numeros.";
                header('Location: index.php?page=register');
                exit;
            }

            if (strlen($cedula) < 6 || strlen($cedula) > 9) {
                $_SESSION['error'] = "La cedula debe tener entre 6 y 9 digitos.";
                header('Location: index.php?page=register');
                exit;
            }

            $id_carrera = !empty($_POST['id_carrera']) ? intval($_POST['id_carrera']) : null;

            $datos = [
                'tipo_documento' => $_POST['tipo_documento'] ?? 'V',
                'cedula' => $_POST['cedula'],
                'nombre' => $_POST['nombre'],
                'segundo_nombre' => $_POST['segundo_nombre'] ?? null,
                'apellido' => $_POST['apellido'],
                'segundo_apellido' => $_POST['segundo_apellido'] ?? null,
                'telefono' => $_POST['telefono'] ?? null,
                'direccion' => $_POST['direccion'] ?? null,
                'correo' => $_POST['correo'],
                'clave' => $clave,
                'rol' => $rol,
                'id_carrera' => $id_carrera,
                'preguntas' => [
                    ['id_pregunta' => $_POST['id_pregunta_1'], 'respuesta' => $_POST['respuesta_1']],
                    ['id_pregunta' => $_POST['id_pregunta_2'], 'respuesta' => $_POST['respuesta_2']]
                ]
            ];

            if ($datos['preguntas'][0]['id_pregunta'] === $datos['preguntas'][1]['id_pregunta']) {
                $_SESSION['error'] = "Debe seleccionar dos preguntas de seguridad diferentes.";
                header('Location: index.php?page=register');
                exit;
            }

            try {
                if ($this->modeloUsuario->registrar($datos)) {
                    if ($rol === 'Docente') {
                        $this->modeloUsuario->actualizarEstadoUsuarioPorCorreo($datos['correo'], 'Aprobado');
                        header('Location: index.php?page=login&msg=exito');
                        exit;
                    }

                    if ($rol === 'Estudiante') {
                        $usuario = $this->modeloUsuario->iniciarSesion($datos['correo'], $datos['clave']);
                        if ($usuario) {
                            $_SESSION['id_usuario_temp'] = $usuario['id'];
                            header('Location: index.php?page=upload_docs');
                            exit;
                        }
                    }
                    header('Location: index.php?page=login&msg=exito');
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: index.php?page=register');
                exit;
            }
        }
    }

    public function subirDocumentos() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $token_enviado = $_POST['csrf_token'] ?? '';
            if (empty($token_enviado) || !hash_equals($_SESSION['csrf_token'], $token_enviado)) {
                $_SESSION['error'] = "Error de seguridad: Token CSRF invalido.";
                header('Location: index.php?page=login');
                exit;
            }
            $id_usuario = $_SESSION['id_usuario_temp'] ?? null;
            if (!$id_usuario) {
                header('Location: index.php?page=register');
                exit;
            }

            $archivos = $_FILES['documentos'] ?? null;
            if ($archivos) {
                $upload_dir = 'public/uploads/';
                foreach ($archivos['name'] as $key => $name) {
                    if ($archivos['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $archivos['tmp_name'][$key];
                        $extension = pathinfo($name, PATHINFO_EXTENSION);
                        
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);
            $mime_permitidos = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png',
                ];
                if (!in_array($mime_type, $mime_permitidos)) {
                    $_SESSION['error'] = "Formato de archivo no permitido. Use PDF, Word o imagenes.";
                    continue;
                }
                
                if (in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])) {
                    if (in_array(strtolower($extension), ['pdf', 'doc', 'docx'])) {
                        $tipo = (strtolower($extension) === 'pdf') ? 'PDF' : 'Word';
                    } else {
                        $tipo = 'Imagen';
                    }
                    $nuevo_nombre = 'doc_' . $id_usuario . '_' . time() . '_' . $key . '.' . $extension;
                    $ruta = $upload_dir . $nuevo_nombre;
                    
                    if (move_uploaded_file($tmp_name, $ruta)) {
                        $this->modeloUsuario->subirDocumento($id_usuario, $name, $ruta, $tipo);
                    }
                }
                    }
                }
                unset($_SESSION['id_usuario_temp']);
                header('Location: index.php?page=login&msg=registro_completo');
                exit;
            }
        }
    }

    public function subirIndividual() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $token_enviado = $_POST['csrf_token'] ?? '';
            if (empty($token_enviado) || !hash_equals($_SESSION['csrf_token'], $token_enviado)) {
                echo json_encode(['success' => false, 'message' => 'Error de seguridad: Token CSRF invalido.']);
                exit;
            }
            $id_usuario = $_SESSION['id_usuario_temp'] ?? $_SESSION['id_usuario'] ?? null;
            if (!$id_usuario) {
                echo json_encode(['success' => false, 'message' => 'Sesion no valida']);
                exit;
            }

            $archivo = $_FILES['archivo'] ?? null;
            $requisito = $_POST['requisito'] ?? 'Documento';
            $requisito_id = $_POST['requisito_id'] ?? '';

            if ($archivo && $archivo['error'] === UPLOAD_ERR_OK) {
                $tamano_maximo = 10 * 1024 * 1024;
                if ($archivo['size'] > $tamano_maximo) {
                    echo json_encode(['success' => false, 'message' => 'El archivo excede el limite de 10MB']);
                    exit;
                }

                $usuario = $this->modeloUsuario->obtenerUsuarioPorId($id_usuario);
                if (!$usuario) {
                    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                    exit;
                }

                $cedula_numerica = preg_replace('/[^0-9]/', '', $usuario['cedula']);
                $relative_upload_path = 'public/uploads/' . $cedula_numerica . '/';
                $absolute_upload_path = __DIR__ . '/../../' . $relative_upload_path;

                if (!is_dir($absolute_upload_path)) {
                    mkdir($absolute_upload_path, 0777, true);
                }

                $name = $archivo['name'];
                $tmp_name = $archivo['tmp_name'];
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);

                $mime_permitidos = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png',
                ];

                if (!in_array($mime_type, $mime_permitidos)) {
                    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Use PDF, Word o imagenes.']);
                    exit;
                }
                
                if (in_array($extension, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])) {
                    if (in_array($extension, ['pdf', 'doc', 'docx'])) {
                        $tipo = ($extension === 'pdf') ? 'PDF' : 'Word';
                    } else {
                        $tipo = 'Imagen';
                    }
                    
                    $safe_requisito = preg_replace('/[^A-Za-z0-9_\-]/', '_', $requisito);
                    $nuevo_nombre = 'doc_' . time() . '_' . $safe_requisito . '.' . $extension;
                    $ruta = $relative_upload_path . $nuevo_nombre;
                    
                    if (move_uploaded_file($tmp_name, $absolute_upload_path . $nuevo_nombre)) {
                        $docs_existentes = $this->modeloUsuario->obtenerDocumentosUsuario($id_usuario);
                        $doc_a_actualizar = null;
                        foreach ($docs_existentes as $de) {
                            $nombre_db = $de['nombre_archivo'];
                            if ($nombre_db === $requisito || (!empty($requisito_id) && $nombre_db === $requisito_id)
                                || stripos($nombre_db, $requisito) !== false || stripos($requisito, $nombre_db) !== false
                                || (!empty($requisito_id) && stripos($nombre_db, $requisito_id) !== false)) {
                                if ($de['estado'] === 'Rechazado' || $de['estado'] === 'Pendiente') {
                                    $doc_a_actualizar = $de['id'];
                                    break;
                                }
                            }
                        }

                        if ($doc_a_actualizar) {
                            $exito = $this->modeloUsuario->actualizarRutaDocumento($doc_a_actualizar, $ruta);
                        } else {
                            $exito = $this->modeloUsuario->subirDocumento($id_usuario, $requisito, $ruta, $tipo);
                        }

                        if ($exito) {
                            $this->modeloUsuario->actualizarEstadoUsuario($id_usuario, 'Pendiente');
                            
                            echo json_encode(['success' => true, 'message' => 'Archivo subido correctamente', 'archivo' => $name, 'tipo' => $tipo]);
                            exit;
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Error al registrar el documento en la base de datos']);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error al mover el archivo al servidor']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Extension de archivo no permitida: ' . $extension]);
                    exit;
                }
            } else {
                $error_code = $archivo['error'] ?? 'unknown';
                echo json_encode(['success' => false, 'message' => 'Error de subida PHP: ' . $error_code]);
                exit;
            }
        }
    }

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token_enviado = $_POST['csrf_token'] ?? '';
            if (empty($token_enviado) || !hash_equals($_SESSION['csrf_token'], $token_enviado)) {
                $_SESSION['error'] = "Error de seguridad: Token CSRF invalido.";
                header('Location: index.php?page=reset_password');
                exit;
            }
            $step = $_POST['step'] ?? '1';

            if ($step == '1') {
                $correo = trim($_POST['correo'] ?? '');
                $usuario = $this->modeloUsuario->obtenerUsuarioPorCorreo($correo);
                
                if (!$usuario) {
                    registrarIntentoFallido($this->db, 'reset_password', $correo);
                    $_SESSION['error'] = "El correo no esta registrado.";
                    header('Location: index.php?page=reset_password');
                    exit;
                }

                $preguntas = $this->modeloUsuario->obtenerPreguntasRespondidas($usuario['id']);
                if (empty($preguntas)) {
                    $_SESSION['error'] = "Su cuenta no tiene preguntas de seguridad configuradas. Contacte a soporte.";
                    header('Location: index.php?page=reset_password');
                    exit;
                }

                $_SESSION['reset_user_id'] = $usuario['id'];
                $_SESSION['reset_step'] = '2';
                $_SESSION['reset_questions'] = $preguntas;
                header('Location: index.php?page=reset_password');
                exit;

            } elseif ($step == '2') {
                $uid = $_SESSION['reset_user_id'] ?? null;
                $respuestas_user = $_POST['respuestas'] ?? [];
                $preguntas_db = $_SESSION['reset_questions'] ?? [];

                if (!$uid || empty($preguntas_db)) {
                    header('Location: index.php?page=reset_password');
                    exit;
                }

                $todas_correctas = true;
                foreach ($preguntas_db as $p) {
                    $resp_proporcionada = strtoupper(trim($respuestas_user[$p['id']] ?? ''));
                    if (!password_verify($resp_proporcionada, $p['hash_respuesta'])) {
                        $todas_correctas = false;
                        break;
                    }
                }

                if (!$todas_correctas) {
                    registrarIntentoFallido($this->db, 'reset_password', 'user_' . $uid);
                    $_SESSION['error'] = "Respuestas de seguridad incorrectas.";
                    header('Location: index.php?page=reset_password');
                    exit;
                }

                $_SESSION['reset_step'] = '3';
                header('Location: index.php?page=reset_password');
                exit;

            } elseif ($step == '3') {
                $uid = $_SESSION['reset_user_id'] ?? null;
                $nueva_clave = $_POST['clave'] ?? '';
                $confirm_clave = $_POST['confirm_clave'] ?? '';

                if (!$uid) {
                    header('Location: index.php?page=reset_password');
                    exit;
                }

                if ($nueva_clave !== $confirm_clave) {
                    $_SESSION['error'] = "Las contrasenas no coinciden.";
                    header('Location: index.php?page=reset_password');
                    exit;
                }

                if (strlen($nueva_clave) < 8) {
                    $_SESSION['error'] = "La contrasena debe tener al menos 8 caracteres.";
                    header('Location: index.php?page=reset_password');
                    exit;
                }

                if ($this->modeloUsuario->actualizarClave($uid, $nueva_clave)) {
                    limpiarIntentosFallidos($this->db, 'reset_password');
                    unset($_SESSION['reset_user_id'], $_SESSION['reset_step'], $_SESSION['reset_questions']);
                    $_SESSION['success'] = "Contrasena actualizada correctamente. Ya puedes iniciar sesion.";
                    header('Location: index.php?page=login&msg=clave_actualizada');
                } else {
                    $_SESSION['error'] = "No se pudo actualizar la contrasena.";
                    header('Location: index.php?page=reset_password');
                }
                exit;
            }
        }
    }

    public function salir() {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}

