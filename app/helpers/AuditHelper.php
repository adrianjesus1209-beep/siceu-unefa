<?php
// AUDITORIA DEL SISTEMA
class AuditHelper {
    public static function registrar($conexion, $id_usuario, $accion, $detalle = '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $conexion->prepare(
            "INSERT INTO bitacora (id_usuario, accion, detalle, direccion_ip) VALUES (:uid, :accion, :detalle, :ip)"
        );
        $stmt->execute([
            ':uid' => $id_usuario,
            ':accion' => $accion,
            ':detalle' => $detalle,
            ':ip' => $ip
        ]);
    }

    public static function obtenerUltimos($conexion, $limite = 100) {
        $stmt = $conexion->prepare(
            "SELECT b.id, b.accion, b.detalle, b.direccion_ip, b.fecha_hora,
                    u.correo, p.nombre, p.apellido, u.rol
            FROM bitacora b
            LEFT JOIN usuario u ON b.id_usuario = u.id
            LEFT JOIN perfil p ON u.id_perfil = p.id
            ORDER BY b.fecha_hora DESC
            LIMIT :lim"
        );
        $stmt->bindValue(':lim', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
