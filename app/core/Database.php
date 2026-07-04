<?php
// CONEXION Y ESQUEMA DE BASE DE DATOS
class Database {
    private $host = "localhost";
    private $db_name = "unefa_siceu_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";charset=utf8mb4", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8mb4");
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS `$this->db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $this->conn->exec("USE `$this->db_name`;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `carrera` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `codigo_carrera` varchar(20) NOT NULL,
                `nombre_carrera` varchar(100) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `codigo_carrera` (`codigo_carrera`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `perfil` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `tipo_documento` varchar(2) DEFAULT 'V',
                `cedula` varchar(20) NOT NULL,
                `nombre` varchar(50) NOT NULL,
                `segundo_nombre` varchar(50) DEFAULT NULL,
                `apellido` varchar(50) NOT NULL,
                `segundo_apellido` varchar(50) DEFAULT NULL,
                `telefono` varchar(20) DEFAULT NULL,
                `direccion` text DEFAULT NULL,
                `id_carrera` int(11) DEFAULT NULL,
                `foto_perfil` varchar(255) DEFAULT NULL,
                `fecha_carnetizacion` datetime DEFAULT NULL,
                `id_usuario` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `id_carrera` (`id_carrera`),
                KEY `cedula` (`cedula`),
                KEY `id_usuario` (`id_usuario`),
                CONSTRAINT `perfil_ibfk_1` FOREIGN KEY (`id_carrera`) REFERENCES `carrera` (`id`) ON DELETE SET NULL,
                CONSTRAINT `perfil_ibfk_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `usuario` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_perfil` int(11) NOT NULL,
                `correo` varchar(100) NOT NULL,
                `clave` varchar(255) NOT NULL,
                `rol` varchar(20) NOT NULL,
                `estado` enum('Pendiente','Aprobado','Rechazado','Inactivo') NOT NULL DEFAULT 'Pendiente',
                PRIMARY KEY (`id`),
                UNIQUE KEY `correo` (`correo`),
                KEY `id_perfil` (`id_perfil`),
                CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_perfil`) REFERENCES `perfil` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `materia` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `codigo_materia` varchar(20) NOT NULL,
                `nombre_materia` varchar(100) NOT NULL,
                `semestre` int(11) DEFAULT NULL,
                `uc` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `codigo_materia` (`codigo_materia`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `seccion` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_materia` int(11) NOT NULL,
                `id_docente` int(11) NOT NULL,
                `nombre_seccion` varchar(20) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `id_materia` (`id_materia`),
                KEY `id_docente` (`id_docente`),
                CONSTRAINT `seccion_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id`) ON DELETE CASCADE,
                CONSTRAINT `seccion_ibfk_2` FOREIGN KEY (`id_docente`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `solicitud_inscripcion` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_estudiante` int(11) NOT NULL,
                `id_seccion` int(11) NOT NULL,
                `estado` enum('Pendiente','Aceptada','Rechazada') NOT NULL DEFAULT 'Pendiente',
                `nota` int(11) DEFAULT 0,
                `ciclo_cerrado` tinyint(1) NOT NULL DEFAULT 0,
                `valido_coordinador` tinyint(1) DEFAULT NULL,
                `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `id_estudiante` (`id_estudiante`),
                KEY `id_seccion` (`id_seccion`),
                CONSTRAINT `solicitud_inscripcion_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
                CONSTRAINT `solicitud_inscripcion_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            try { $this->conn->exec("ALTER TABLE `solicitud_inscripcion` ADD `ciclo_cerrado` tinyint(1) NOT NULL DEFAULT 0 AFTER `nota`"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }
            try { $this->conn->exec("ALTER TABLE `solicitud_inscripcion` ADD `valido_coordinador` tinyint(1) DEFAULT NULL AFTER `ciclo_cerrado`"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }
            try { $this->conn->exec("ALTER TABLE `perfil` ADD `semestre_actual` int(11) NOT NULL DEFAULT 0 AFTER `fecha_carnetizacion`"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }
            try { $this->conn->exec("ALTER TABLE `perfil` ADD `id_usuario` int(11) DEFAULT NULL AFTER `fecha_carnetizacion`"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }
            try { $this->conn->exec("ALTER TABLE `perfil` ADD CONSTRAINT `perfil_ibfk_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE SET NULL"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }
            try { $this->conn->exec("UPDATE perfil p JOIN usuario u ON u.id_perfil = p.id SET p.id_usuario = u.id WHERE p.id_usuario IS NULL"); } catch (PDOException $e) {}

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `prelacion` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_materia` int(11) NOT NULL,
                `id_prerrequisito` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_prelacion` (`id_materia`,`id_prerrequisito`),
                CONSTRAINT `prelacion_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id`) ON DELETE CASCADE,
                CONSTRAINT `prelacion_ibfk_2` FOREIGN KEY (`id_prerrequisito`) REFERENCES `materia` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `registro_documentos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_usuario` int(11) NOT NULL,
                `nombre_archivo` varchar(255) NOT NULL,
                `ruta` varchar(255) NOT NULL,
                `tipo` enum('Word','PDF','Imagen') NOT NULL DEFAULT 'PDF',
                `estado` enum('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
                `observaciones` text DEFAULT NULL,
                `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `id_usuario` (`id_usuario`),
                CONSTRAINT `registro_documentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `preguntas_seguridad` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `texto_pregunta` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `respuestas_seguridad_usuario` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_usuario` int(11) NOT NULL,
                `id_pregunta` int(11) NOT NULL,
                `hash_respuesta` varchar(255) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `id_usuario` (`id_usuario`),
                KEY `id_pregunta` (`id_pregunta`),
                CONSTRAINT `respuestas_seguridad_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
                CONSTRAINT `respuestas_seguridad_usuario_ibfk_2` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas_seguridad` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `bitacora` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_usuario` int(11) DEFAULT NULL,
                `accion` varchar(100) NOT NULL,
                `detalle` text DEFAULT NULL,
                `direccion_ip` varchar(45) DEFAULT NULL,
                `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `id_usuario` (`id_usuario`),
                KEY `fecha_hora` (`fecha_hora`),
                CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `intentos_fallidos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `direccion_ip` varchar(45) NOT NULL,
                `tipo` varchar(20) NOT NULL DEFAULT 'login',
                `identificador` varchar(100) DEFAULT NULL,
                `fecha_intento` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `direccion_ip` (`direccion_ip`),
                KEY `tipo` (`tipo`),
                KEY `fecha_intento` (`fecha_intento`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("INSERT IGNORE INTO carrera (codigo_carrera, nombre_carrera) VALUES ('SYS', 'IngenierÃ­a de Sistemas')");

            $conteo = $this->conn->query("SELECT COUNT(*) FROM materia")->fetchColumn();
            if ($conteo == 0) {
                $materias_seed = [
                    ['FIL-001','FilosofÃ­a, Ã‰tica y Valores',0,2], ['LEN-001','Lenguaje y ComunicaciÃ³n',0,3], ['MAT-000','MatemÃ¡tica',0,3],
                    ['DIB-101','Dibujo',1,2], ['EDA-101','EducaciÃ³n Ambiental',1,1], ['GEO-101','GeometrÃ­a AnalÃ­tica',1,4],
                    ['HSC-101','Hombre, Sociedad, Ciencia y TecnologÃ­a',1,2], ['ING-101','InglÃ©s I',1,2], ['MAT-101','MatemÃ¡ticas I',1,5],
                    ['SEM-101','Seminario I',1,1], ['ALG-201','Ãlgebra Lineal',2,4], ['FIL-201','FÃ­sica I Laboratorio',2,1],
                    ['FIS-201','FÃ­sica I',2,4], ['ING-201','InglÃ©s II',2,2], ['MAT-201','MatemÃ¡ticas II',2,5],
                    ['QUI-201','QuÃ­mica General',2,3], ['SEM-201','Seminario II',2,1], ['FIS-301','FÃ­sica II',3,4],
                    ['MAT-301','MatemÃ¡ticas III',3,5], ['PRO-301','Probabilidad y EstadÃ­stica',3,3], ['PRG-301','ProgramaciÃ³n (Pascal)',3,3],
                    ['SAD-301','Sistemas Administrativos',3,2], ['CAL-401','CÃ¡lculo NumÃ©rico',4,3], ['LP1-401','Lenguajes de ProgramaciÃ³n I (C/C++)',4,3],
                    ['LOG-401','LÃ³gica MatemÃ¡tica',4,3], ['PDA-401','Procesamiento de Datos',4,2], ['SPR-401','Sistemas de ProducciÃ³n',4,3],
                    ['TSI-401','TeorÃ­as de Sistemas',4,3], ['ASI-501','AnÃ¡lisis de Sistemas',5,4], ['BD0-501','Base de Datos',5,3],
                    ['CBA-501','CÃ¡tedra Bolivariana I',5,2], ['CIR-501','Circuitos LÃ³gicos',5,3], ['INO-501','InvestigaciÃ³n de Operaciones',5,3],
                    ['LP2-501','Lenguajes de ProgramaciÃ³n II (Java)',5,3], ['TGR-501','TeorÃ­a de Grafos',5,3], ['ARC-601','Arquitectura del Computador',6,3],
                    ['CBB-601','CÃ¡tedra Bolivariana II',6,2], ['DSI-601','DiseÃ±o de Sistemas',6,4], ['LP3-601','Lenguaje de ProgramaciÃ³n III (HTML5, CSS3, PHP)',6,3],
                    ['ONL-601','OptimizaciÃ³n No Lineal',6,3], ['PES-601','Procesos EstocÃ¡sticos',6,3], ['SOP-601','Sistemas Operativos',6,3],
                    ['GIN-701','Gerencia de la InformÃ¡tica',7,3], ['ISI-701','ImplantaciÃ³n de Sistemas',7,4], ['MIN-701','MetodologÃ­a de la InvestigaciÃ³n',7,3],
                    ['RED-701','Redes',7,3], ['SIM-701','SimulaciÃ³n y Modelos',7,3], ['AUS-801','AuditorÃ­a de Sistemas',8,3],
                    ['MLE-801','Marco Legal para el Ejercicio de la IngenierÃ­a',8,2], ['TEL-801','Teleprocesos',8,3], ['TDE-801','TeorÃ­a de Decisiones',8,3],
                    ['ET-AS','Arquitectura de Software',null,3], ['ET-IA','Inteligencia Artificial',null,3], ['ET-RL','Redes de Ãrea Local',null,3],
                    ['ET-SABD','Sistemas Avanzados de Bases de Datos',null,3], ['ET-TR','TecnologÃ­a de Redes',null,3],
                    ['ENT-DOI','Decisiones Ã“ptimas de InversiÃ³n',null,3], ['ENT-GP','Gerencia de Proyectos',null,3],
                    ['ENT-INF','InformÃ¡tica',null,3], ['ENT-IM','IngenierÃ­a de MÃ©todos',null,3], ['ENT-PG','Principios de Gerencia',null,3],
                    ['DEF-000','Defensa Integral',0,2], ['DEF-101','Defensa Integral I',1,2],
                    ['DEF-201','Defensa Integral II',2,2], ['DEF-301','Defensa Integral III',3,2],
                    ['DEF-401','Defensa Integral IV',4,2], ['DEF-501','Defensa Integral V',5,2],
                    ['DEF-601','Defensa Integral VI',6,2], ['DEF-701','Defensa Integral VII',7,2],
                    ['DEF-801','Defensa Integral VIII',8,2]
                ];
                $stmt_m = $this->conn->prepare("INSERT INTO materia (codigo_materia, nombre_materia, semestre, uc) VALUES (?, ?, ?, ?)");
                foreach ($materias_seed as $m) {
                    $stmt_m->execute($m);
                }
                $this->conn->exec("UPDATE materia SET orden = 99 WHERE codigo_materia LIKE 'DEF-%'");
            }

            $conteo = $this->conn->query("SELECT COUNT(*) FROM preguntas_seguridad")->fetchColumn();
            if ($conteo == 0) {
                $preguntas_seed = [
                    ['Â¿En quÃ© ciudad naciste?'],
                    ['Â¿CuÃ¡l es el primer nombre de tu madre?'],
                    ['Â¿CÃ³mo se llamaba tu primer colegio?'],
                    ['Â¿CÃ³mo se llamaba tu primera mascota?'],
                    ['Â¿CuÃ¡l es tu segundo nombre?'],
                    ['Â¿En quÃ© mes es tu cumpleaÃ±os?'],
                    ['Â¿CuÃ¡l es tu fruta favorita?'],
                    ['Â¿QuÃ© idioma hablas en casa?'],
                    ['Â¿CuÃ¡l es tu color favorito?']
                ];
                $stmt_q = $this->conn->prepare("INSERT INTO preguntas_seguridad (texto_pregunta) VALUES (?)");
                foreach ($preguntas_seed as $pq) {
                    $stmt_q->execute($pq);
                }
            }

            $clave_seed = password_hash('12345678', PASSWORD_BCRYPT);
            $verificar_coord = $this->conn->query("SELECT COUNT(*) FROM usuario WHERE correo = 'coordinadora@unefa.edu.ve'")->fetchColumn();
            if ($verificar_coord == 0) {
                $this->conn->exec("INSERT INTO perfil (tipo_documento, cedula, nombre, apellido) VALUES ('V', '12345678', 'Coordinadora', 'UNEFA')");
                $id_perfil_coord = $this->conn->lastInsertId();
                $this->conn->exec("INSERT INTO usuario (id_perfil, correo, clave, rol, estado) VALUES ($id_perfil_coord, 'coordinadora@unefa.edu.ve', '$clave_seed', 'Coordinador', 'Aprobado')");
                $this->conn->exec("UPDATE perfil SET id_usuario = (SELECT id FROM usuario WHERE id_perfil = $id_perfil_coord) WHERE id = $id_perfil_coord");
            }

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `periodo_academico` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `nombre` varchar(50) NOT NULL,
                `fecha_inicio` date NOT NULL,
                `fecha_fin` date DEFAULT NULL,
                `estado` varchar(20) NOT NULL DEFAULT 'Planificado',
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            try { $this->conn->exec("ALTER TABLE `periodo_academico` ADD `estado` varchar(20) NOT NULL DEFAULT 'Planificado' AFTER `fecha_fin`"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }
            try {
                $this->conn->exec("UPDATE `periodo_academico` SET `estado` = 'Activo' WHERE `activo` = 1 AND `estado` = 'Planificado'");
                $this->conn->exec("ALTER TABLE `periodo_academico` DROP COLUMN `activo`");
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'check that column/key exists') === false && strpos($e->getMessage(), 'Unknown column') === false) throw $e;
            }
            try { $this->conn->exec("ALTER TABLE `solicitud_inscripcion` ADD `id_periodo` int(11) DEFAULT NULL AFTER `id_seccion`"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }
            try { $this->conn->exec("ALTER TABLE `solicitud_inscripcion` ADD CONSTRAINT `solicitud_inscripcion_ibfk_3` FOREIGN KEY (`id_periodo`) REFERENCES `periodo_academico` (`id`) ON DELETE SET NULL"); } catch (PDOException $e) { if (strpos($e->getMessage(), 'Duplicate') === false) throw $e; }

            $countPeriodos = $this->conn->query("SELECT COUNT(*) FROM periodo_academico")->fetchColumn();
            if ($countPeriodos == 0) {
                $currentYear = date('Y');
                $this->conn->exec("INSERT INTO periodo_academico (nombre, fecha_inicio, estado) VALUES ('{$currentYear}-I', '{$currentYear}-01-01', 'Activo')");
            }

            $this->conn->exec("CREATE TABLE IF NOT EXISTS `cronograma_evento` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `anio` int(11) NOT NULL,
                `categoria` varchar(50) NOT NULL,
                `periodo` varchar(100) NOT NULL,
                `descripcion` varchar(300) NOT NULL,
                `fecha_inicio` date NOT NULL,
                `fecha_fin` date NOT NULL,
                `id_periodo` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `anio` (`anio`),
                KEY `categoria` (`categoria`),
                KEY `id_periodo` (`id_periodo`),
                CONSTRAINT `cronograma_evento_ibfk_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `periodo_academico` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $this->conn->exec("SET time_zone = '-04:00';");
        } catch(PDOException $excepcion) {
            die("Error de Base de Datos: " . $excepcion->getMessage());
        }
        return $this->conn;
    }
}

