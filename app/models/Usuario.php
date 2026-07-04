<?php
// MODELO DE USUARIO
class Usuario {
    private $controlador;

    public function __construct($db) {
        $this->controlador = $db;
    }

    public function registrar($datos) {
        try {
            $this->controlador->beginTransaction();

            $consulta_perfil = "INSERT INTO perfil (tipo_documento, cedula, nombre, segundo_nombre, apellido, segundo_apellido, telefono, direccion, id_carrera) 
                                VALUES (:tipo, :cedula, :nombre, :s_nombre, :apellido, :s_apellido, :telefono, :direccion, :id_carrera)";
            $sentencia_p = $this->controlador->prepare($consulta_perfil);
            $sentencia_p->bindParam(':tipo', $datos['tipo_documento']);
            $sentencia_p->bindParam(':cedula', $datos['cedula']);
            $sentencia_p->bindParam(':nombre', $datos['nombre']);
            $sentencia_p->bindParam(':s_nombre', $datos['segundo_nombre']);
            $sentencia_p->bindParam(':apellido', $datos['apellido']);
            $sentencia_p->bindParam(':s_apellido', $datos['segundo_apellido']);
            $sentencia_p->bindParam(':telefono', $datos['telefono']);
            $sentencia_p->bindParam(':direccion', $datos['direccion']);
            $sentencia_p->bindParam(':id_carrera', $datos['id_carrera']);
            $sentencia_p->execute();
            $id_perfil = $this->controlador->lastInsertId();

            $consulta_usuario = "INSERT INTO usuario (id_perfil, correo, clave, rol) VALUES (:id_perfil, :correo, :clave, :rol)";
            $sentencia_u = $this->controlador->prepare($consulta_usuario);
            $clave_hasheada = password_hash($datos['clave'], PASSWORD_BCRYPT);
            $sentencia_u->bindParam(':id_perfil', $id_perfil);
            $sentencia_u->bindParam(':correo', $datos['correo']);
            $sentencia_u->bindParam(':clave', $clave_hasheada);
            $sentencia_u->bindParam(':rol', $datos['rol']);
            $sentencia_u->execute();
            $id_usuario = $this->controlador->lastInsertId();

            $this->controlador->prepare("UPDATE perfil SET id_usuario = ? WHERE id = ?")->execute([$id_usuario, $id_perfil]);

            if (!empty($datos['preguntas']) && is_array($datos['preguntas'])) {
                foreach ($datos['preguntas'] as $p) {
                    $consulta_ans = "INSERT INTO respuestas_seguridad_usuario (id_usuario, id_pregunta, hash_respuesta) 
                                    VALUES (:uid, :qid, :ans)";
                    $sent_ans = $this->controlador->prepare($consulta_ans);
                    $ans_hash = password_hash(strtoupper(trim($p['respuesta'])), PASSWORD_BCRYPT);
                    $sent_ans->execute([
                        ':uid' => $id_usuario,
                        ':qid' => $p['id_pregunta'],
                        ':ans' => $ans_hash
                    ]);
                }
            }

            $this->controlador->commit();
            return true;
        } catch (Exception $e) {
            $this->controlador->rollBack();
            if ($e->getCode() == 23000) {
                if (stripos($e->getMessage(), 'correo') !== false) {
                    throw new Exception("El correo electrÃ³nico ya estÃ¡ registrado.");
                } elseif (stripos($e->getMessage(), 'cedula') !== false) {
                    throw new Exception("La cÃ©dula de identidad ya estÃ¡ registrada.");
                }
            }
            throw new Exception("Error en el registro: " . $e->getMessage());
        }
    }

    public function iniciarSesion($correo, $clave) {
        $consulta = "SELECT u.*, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.tipo_documento 
                    FROM usuario u 
                    JOIN perfil p ON u.id_perfil = p.id 
                    WHERE u.correo = :correo LIMIT 1";
        $sentencia = $this->controlador->prepare($consulta);
        $sentencia->bindParam(':correo', $correo);
        $sentencia->execute();

        if ($sentencia->rowCount() > 0) {
            $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
            if (password_verify($clave, $fila['clave'])) {
                return $fila;
            }
        }
        return false;
    }

    public function obtenerUsuarioPorId($id) {
        $consulta = "SELECT u.*, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.tipo_documento 
                    FROM usuario u 
                    JOIN perfil p ON u.id_perfil = p.id 
                    WHERE u.id = :id LIMIT 1";
        $sentencia = $this->controlador->prepare($consulta);
        $sentencia->execute([':id' => $id]);
        return $sentencia->fetch(PDO::FETCH_ASSOC);
    }

    public function subirDocumento($id_usuario, $nombre_archivo, $ruta, $tipo) {
        $consulta = "INSERT INTO registro_documentos (id_usuario, nombre_archivo, ruta, tipo) VALUES (:id_usuario, :nombre_archivo, :ruta, :tipo)";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([
            ':id_usuario' => $id_usuario,
            ':nombre_archivo' => $nombre_archivo,
            ':ruta' => $ruta,
            ':tipo' => $tipo
        ]);
    }

    public function obtenerUsuariosPendientes() {
        $consulta = "SELECT u.id, u.correo, p.id as id_perfil, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido, p.cedula, p.telefono, p.direccion 
                    FROM usuario u 
                    JOIN perfil p ON u.id_perfil = p.id 
                    WHERE u.rol = 'Estudiante' AND u.estado = 'Pendiente'";
        $sentencia = $this->controlador->query($consulta);
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarPerfilCompleto($id_perfil, $datos) {
        $consulta = "UPDATE perfil SET cedula = :cedula, nombre = :nombre, segundo_nombre = :s_nombre, apellido = :apellido, segundo_apellido = :s_apellido, telefono = :telefono, direccion = :direccion WHERE id = :id";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([
            ':cedula' => $datos['cedula'],
            ':nombre' => $datos['nombre'],
            ':s_nombre' => $datos['segundo_nombre'] ?? null,
            ':apellido' => $datos['apellido'],
            ':s_apellido' => $datos['segundo_apellido'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':direccion' => $datos['direccion'] ?? null,
            ':id' => $id_perfil
        ]);
    }

    public function obtenerDocumentosUsuario($id_usuario) {
        $consulta = "SELECT * FROM registro_documentos WHERE id_usuario = :id_usuario";
        $sentencia = $this->controlador->prepare($consulta);
        $sentencia->execute([':id_usuario' => $id_usuario]);
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstadoUsuario($id, $estado) {
        $consulta = "UPDATE usuario SET estado = :estado WHERE id = :id";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([':estado' => $estado, ':id' => $id]);
    }

    public function actualizarEstadoDocumento($id_doc, $estado, $observaciones = null) {
        $consulta = "UPDATE registro_documentos SET estado = :estado, observaciones = :obs WHERE id = :id";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([
            ':estado' => $estado, 
            ':obs' => $observaciones,
            ':id' => $id_doc
        ]);
    }

    public function actualizarRutaDocumento($id_doc, $nueva_ruta) {
        $stmt = $this->controlador->prepare("UPDATE registro_documentos SET ruta = :ruta, estado = 'Pendiente', observaciones = NULL WHERE id = :id");
        return $stmt->execute([
            ':ruta' => $nueva_ruta,
            ':id' => $id_doc
        ]);
    }

    public function actualizarEstadoUsuarioPorCorreo($correo, $estado) {
        $consulta = "UPDATE usuario SET estado = :estado WHERE correo = :correo";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([':estado' => $estado, ':correo' => $correo]);
    }

    public function eliminarUsuario($id) {
        $consulta = "DELETE FROM usuario WHERE id = :id";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([':id' => $id]);
    }

    public function obtenerPreguntasSeguridad() {
        $consulta = "SELECT * FROM preguntas_seguridad";
        return $this->controlador->query($consulta)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerUsuarioPorCorreo($correo) {
        $consulta = "SELECT id, correo FROM usuario WHERE correo = :correo LIMIT 1";
        $sentencia = $this->controlador->prepare($consulta);
        $sentencia->execute([':correo' => $correo]);
        return $sentencia->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPreguntasRespondidas($user_id) {
                    $consulta = "SELECT sq.id, sq.texto_pregunta, usa.hash_respuesta 
                    FROM preguntas_seguridad sq
                    JOIN respuestas_seguridad_usuario usa ON sq.id = usa.id_pregunta
                    WHERE usa.id_usuario = :uid";
        $sentencia = $this->controlador->prepare($consulta);
        $sentencia->execute([':uid' => $user_id]);
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPerfilCompleto($user_id) {
        $consulta = "SELECT u.*, p.*, c.nombre_carrera
                    FROM usuario u
                    JOIN perfil p ON u.id_perfil = p.id
                    LEFT JOIN carrera c ON p.id_carrera = c.id
                    WHERE u.id = :id LIMIT 1";
        $sentencia = $this->controlador->prepare($consulta);
        $sentencia->execute([':id' => $user_id]);
        return $sentencia->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarMiPerfil($id_perfil, $datos) {
        $consulta = "UPDATE perfil SET nombre = :nombre, segundo_nombre = :s_nombre, apellido = :apellido, segundo_apellido = :s_apellido, telefono = :telefono, direccion = :direccion WHERE id = :id";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([
            ':nombre' => $datos['nombre'],
            ':s_nombre' => $datos['segundo_nombre'] ?? null,
            ':apellido' => $datos['apellido'],
            ':s_apellido' => $datos['segundo_apellido'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':direccion' => $datos['direccion'] ?? null,
            ':id' => $id_perfil
        ]);
    }

    public function actualizarClave($user_id, $nueva_clave) {
        $hash = password_hash($nueva_clave, PASSWORD_BCRYPT);
        $consulta = "UPDATE usuario SET clave = :hash WHERE id = :id";
        $sentencia = $this->controlador->prepare($consulta);
        return $sentencia->execute([':hash' => $hash, ':id' => $user_id]);
    }
}

