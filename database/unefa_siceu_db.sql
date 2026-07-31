-- ========================================================
-- AUTO-GENERATED DATABASE EXPORT
-- Database: `unefa_siceu_db` 
-- Exported on: 2026-07-30 01:10:58
-- ========================================================

CREATE DATABASE IF NOT EXISTS `unefa_siceu_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unefa_siceu_db`;

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: unefa_siceu_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bitacora` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacora`
--

LOCK TABLES `bitacora` WRITE;
/*!40000 ALTER TABLE `bitacora` DISABLE KEYS */;
/*!40000 ALTER TABLE `bitacora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carrera`
--

DROP TABLE IF EXISTS `carrera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carrera` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_carrera` varchar(20) NOT NULL,
  `nombre_carrera` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_carrera` (`codigo_carrera`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carrera`
--

LOCK TABLES `carrera` WRITE;
/*!40000 ALTER TABLE `carrera` DISABLE KEYS */;
INSERT INTO `carrera` VALUES (1,'SYS','Ingeniería de Sistemas');
/*!40000 ALTER TABLE `carrera` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cronograma_evento`
--

DROP TABLE IF EXISTS `cronograma_evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cronograma_evento` (
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
  KEY `id_periodo` (`id_periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cronograma_evento`
--

LOCK TABLES `cronograma_evento` WRITE;
/*!40000 ALTER TABLE `cronograma_evento` DISABLE KEYS */;
/*!40000 ALTER TABLE `cronograma_evento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intentos_fallidos`
--

DROP TABLE IF EXISTS `intentos_fallidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intentos_fallidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `direccion_ip` varchar(45) NOT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'login',
  `identificador` varchar(100) DEFAULT NULL,
  `fecha_intento` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `direccion_ip` (`direccion_ip`),
  KEY `tipo` (`tipo`),
  KEY `fecha_intento` (`fecha_intento`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intentos_fallidos`
--

LOCK TABLES `intentos_fallidos` WRITE;
/*!40000 ALTER TABLE `intentos_fallidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `intentos_fallidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materia`
--

DROP TABLE IF EXISTS `materia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_materia` varchar(20) NOT NULL,
  `nombre_materia` varchar(100) NOT NULL,
  `semestre` int(11) DEFAULT NULL,
  `uc` int(11) DEFAULT 3,
  `orden` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_materia` (`codigo_materia`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materia`
--

LOCK TABLES `materia` WRITE;
/*!40000 ALTER TABLE `materia` DISABLE KEYS */;
INSERT INTO `materia` VALUES (1,'FIL-001','Filosofía, Ética y Valores',0,3,1),(2,'LEN-001','Lenguaje y Comunicación',0,3,1),(3,'MAT-000','Matemática',0,3,1),(4,'DIB-101','Dibujo',1,3,1),(5,'EDA-101','Educación Ambiental',1,3,1),(6,'GEO-101','Geometría Analítica',1,3,1),(7,'HSC-101','Hombre, Sociedad, Ciencia y Tecnología',1,3,1),(8,'ING-101','Inglés I',1,3,1),(9,'MAT-101','Matemáticas I',1,3,1),(10,'SEM-101','Seminario I',1,3,1),(11,'ALG-201','Álgebra Lineal',2,3,1),(12,'FIL-201','Física I Laboratorio',2,3,1),(13,'FIS-201','Física I',2,3,1),(14,'ING-201','Inglés II',2,3,1),(15,'MAT-201','Matemáticas II',2,3,1),(16,'QUI-201','Química General',2,3,1),(17,'SEM-201','Seminario II',2,3,1),(18,'FIS-301','Física II',3,3,1),(19,'MAT-301','Matemáticas III',3,3,1),(20,'PRO-301','Probabilidad y Estadística',3,3,1),(21,'PRG-301','Programación (Pascal)',3,3,1),(22,'SAD-301','Sistemas Administrativos',3,3,1),(23,'CAL-401','Cálculo Numérico',4,3,1),(24,'LP1-401','Lenguajes de Programación I (C/C++)',4,3,1),(25,'LOG-401','Lógica Matemática',4,3,1),(26,'PDA-401','Procesamiento de Datos',4,3,1),(27,'SPR-401','Sistemas de Producción',4,3,1),(28,'TSI-401','Teorías de Sistemas',4,3,1),(29,'ASI-501','Análisis de Sistemas',5,3,1),(30,'BD0-501','Base de Datos',5,3,1),(31,'CBA-501','Cátedra Bolivariana I',5,3,1),(32,'CIR-501','Circuitos Lógicos',5,3,1),(33,'INO-501','Investigación de Operaciones',5,3,1),(34,'LP2-501','Lenguajes de Programación II (Java)',5,3,1),(35,'TGR-501','Teoría de Grafos',5,3,1),(36,'ARC-601','Arquitectura del Computador',6,3,1),(37,'CBB-601','Cátedra Bolivariana II',6,3,1),(38,'DSI-601','Diseño de Sistemas',6,3,1),(39,'LP3-601','Lenguaje de Programación III (HTML5, CSS3, PHP)',6,3,1),(40,'ONL-601','Optimización No Lineal',6,3,1),(41,'PES-601','Procesos Estocásticos',6,3,1),(42,'SOP-601','Sistemas Operativos',6,3,1),(43,'GIN-701','Gerencia de la Informática',7,3,1),(44,'ISI-701','Implantación de Sistemas',7,3,1),(45,'MIN-701','Metodología de la Investigación',7,3,1),(46,'RED-701','Redes',7,3,1),(47,'SIM-701','Simulación y Modelos',7,3,1),(48,'AUS-801','Auditoría de Sistemas',8,3,1),(49,'MLE-801','Marco Legal para el Ejercicio de la Ingeniería',8,3,1),(50,'TEL-801','Teleprocesos',8,3,1),(51,'TDE-801','Teoría de Decisiones',8,3,1),(52,'ET-AS','Arquitectura de Software',NULL,3,1),(53,'ET-IA','Inteligencia Artificial',NULL,3,1),(54,'ET-RL','Redes de Área Local',NULL,3,1),(55,'ET-SABD','Sistemas Avanzados de Bases de Datos',NULL,3,1),(56,'ET-TR','Tecnología de Redes',NULL,3,1),(57,'ENT-DOI','Decisiones Óptimas de Inversión',NULL,3,1),(58,'ENT-GP','Gerencia de Proyectos',NULL,3,1),(59,'ENT-INF','Informática',NULL,3,1),(60,'ENT-IM','Ingeniería de Métodos',NULL,3,1),(61,'ENT-PG','Principios de Gerencia',NULL,3,1),(62,'DEF-000','Defensa Integral',0,2,1),(63,'DEF-101','Defensa Integral I',1,2,1),(64,'DEF-201','Defensa Integral II',2,2,1),(65,'DEF-301','Defensa Integral III',3,2,1),(66,'DEF-401','Defensa Integral IV',4,2,1),(67,'DEF-501','Defensa Integral V',5,2,1),(68,'DEF-601','Defensa Integral VI',6,2,1),(69,'DEF-701','Defensa Integral VII',7,2,1),(70,'DEF-801','Defensa Integral VIII',8,2,1);
/*!40000 ALTER TABLE `materia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfil`
--

DROP TABLE IF EXISTS `perfil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perfil` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `tipo_documento` varchar(2) DEFAULT 'V',
  `nombre` varchar(50) NOT NULL,
  `segundo_nombre` varchar(50) DEFAULT NULL,
  `apellido` varchar(50) NOT NULL,
  `segundo_apellido` varchar(50) DEFAULT NULL,
  `id_carrera` int(11) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `fecha_carnetizacion` datetime DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `semestre_actual` int(11) NOT NULL DEFAULT 0,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_carrera` (`id_carrera`),
  KEY `perfil_ibfk_usuario` (`id_usuario`),
  CONSTRAINT `perfil_ibfk_1` FOREIGN KEY (`id_carrera`) REFERENCES `carrera` (`id`) ON DELETE SET NULL,
  CONSTRAINT `perfil_ibfk_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfil`
--

LOCK TABLES `perfil` WRITE;
/*!40000 ALTER TABLE `perfil` DISABLE KEYS */;
INSERT INTO `perfil` VALUES (1,'V-COORD','V','COORDINADORA','','ACADEMICA','',1,'default.svg',NULL,1,0,'','');
/*!40000 ALTER TABLE `perfil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periodo_academico`
--

DROP TABLE IF EXISTS `periodo_academico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `periodo_academico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Planificado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periodo_academico`
--

LOCK TABLES `periodo_academico` WRITE;
/*!40000 ALTER TABLE `periodo_academico` DISABLE KEYS */;
INSERT INTO `periodo_academico` VALUES (1,'2026-I','2026-01-01',NULL,'Activo','2026-07-02 05:47:47');
/*!40000 ALTER TABLE `periodo_academico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preguntas_seguridad`
--

DROP TABLE IF EXISTS `preguntas_seguridad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preguntas_seguridad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `texto_pregunta` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preguntas_seguridad`
--

LOCK TABLES `preguntas_seguridad` WRITE;
/*!40000 ALTER TABLE `preguntas_seguridad` DISABLE KEYS */;
INSERT INTO `preguntas_seguridad` VALUES (1,'¿En qué ciudad naciste?'),(2,'¿Cuál es el primer nombre de tu madre?'),(3,'¿Cómo se llamaba tu primer colegio?'),(4,'¿Cómo se llamaba tu primera mascota?'),(5,'¿Cuál es tu segundo nombre?'),(6,'¿En qué mes es tu cumpleaños?'),(7,'¿Cuál es tu fruta favorita?'),(8,'¿Qué idioma hablas en casa?'),(9,'¿Cuál es tu color favorito?');
/*!40000 ALTER TABLE `preguntas_seguridad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prelacion`
--

DROP TABLE IF EXISTS `prelacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prelacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_materia` int(11) NOT NULL,
  `id_prerrequisito` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prelacion` (`id_materia`,`id_prerrequisito`),
  KEY `id_prerrequisito` (`id_prerrequisito`),
  CONSTRAINT `prelacion_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prelacion_ibfk_2` FOREIGN KEY (`id_prerrequisito`) REFERENCES `materia` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prelacion`
--

LOCK TABLES `prelacion` WRITE;
/*!40000 ALTER TABLE `prelacion` DISABLE KEYS */;
INSERT INTO `prelacion` VALUES (16,13,6),(14,14,8),(7,15,9),(15,17,10),(17,18,13),(8,19,15),(25,20,7),(34,22,14),(9,23,19),(31,24,21),(18,25,11),(30,26,17),(42,27,24),(43,27,29),(35,28,22),(36,29,28),(26,30,20),(19,32,25),(10,33,23),(32,34,24),(22,35,18),(20,36,32),(37,38,29),(33,39,34),(11,40,33),(23,41,35),(27,42,30),(40,43,29),(21,43,36),(38,44,38),(24,45,41),(28,46,42),(12,47,40),(39,48,44),(41,49,44),(29,50,46),(13,51,47);
/*!40000 ALTER TABLE `prelacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registro_documentos`
--

DROP TABLE IF EXISTS `registro_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registro_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `tipo` enum('Word','PDF','Imagen','Excel') NOT NULL DEFAULT 'PDF',
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `registro_documentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registro_documentos`
--

LOCK TABLES `registro_documentos` WRITE;
/*!40000 ALTER TABLE `registro_documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `registro_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `respuestas_seguridad_usuario`
--

DROP TABLE IF EXISTS `respuestas_seguridad_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `respuestas_seguridad_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_pregunta` int(11) NOT NULL,
  `hash_respuesta` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `respuestas_seguridad_usuario_ibfk_1` (`id_usuario`),
  KEY `respuestas_seguridad_usuario_ibfk_2` (`id_pregunta`),
  CONSTRAINT `respuestas_seguridad_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `respuestas_seguridad_usuario_ibfk_2` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas_seguridad` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `respuestas_seguridad_usuario`
--

LOCK TABLES `respuestas_seguridad_usuario` WRITE;
/*!40000 ALTER TABLE `respuestas_seguridad_usuario` DISABLE KEYS */;
/*!40000 ALTER TABLE `respuestas_seguridad_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seccion`
--

DROP TABLE IF EXISTS `seccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seccion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_materia` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `nombre_seccion` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_materia` (`id_materia`),
  KEY `id_docente` (`id_docente`),
  CONSTRAINT `seccion_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seccion_ibfk_2` FOREIGN KEY (`id_docente`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seccion`
--

LOCK TABLES `seccion` WRITE;
/*!40000 ALTER TABLE `seccion` DISABLE KEYS */;
/*!40000 ALTER TABLE `seccion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitud_inscripcion`
--

DROP TABLE IF EXISTS `solicitud_inscripcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitud_inscripcion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `id_periodo` int(11) DEFAULT NULL,
  `estado` enum('Pendiente','Aceptada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `nota` int(11) DEFAULT NULL,
  `ciclo_cerrado` tinyint(1) NOT NULL DEFAULT 0,
  `valido_coordinador` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_estudiante` (`id_estudiante`),
  KEY `id_seccion` (`id_seccion`),
  KEY `solicitud_inscripcion_ibfk_3` (`id_periodo`),
  CONSTRAINT `solicitud_inscripcion_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `solicitud_inscripcion_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id`) ON DELETE CASCADE,
  CONSTRAINT `solicitud_inscripcion_ibfk_3` FOREIGN KEY (`id_periodo`) REFERENCES `periodo_academico` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitud_inscripcion`
--

LOCK TABLES `solicitud_inscripcion` WRITE;
/*!40000 ALTER TABLE `solicitud_inscripcion` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitud_inscripcion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,1,'coordinadora@unefa.edu.ve','$2y$10$S1.rBMWTk1J7RqNw.UL8qu3GBaeK4yh2PLO4yRQZw95eawUqh2qXi','Coordinador','Aprobado');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'unefa_siceu_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-29 19:10:59
