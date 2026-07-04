-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-07-2026 a las 08:11:56
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `unefa_siceu_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `detalle` text DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrera`
--

CREATE TABLE `carrera` (
  `id` int(11) NOT NULL,
  `codigo_carrera` varchar(20) NOT NULL,
  `nombre_carrera` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carrera`
--

INSERT INTO `carrera` (`id`, `codigo_carrera`, `nombre_carrera`) VALUES
(1, 'SYS', 'Ingeniería de Sistemas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cronograma_evento`
--

CREATE TABLE `cronograma_evento` (
  `id` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `descripcion` varchar(300) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `id_periodo` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentos_fallidos`
--

CREATE TABLE `intentos_fallidos` (
  `id` int(11) NOT NULL,
  `direccion_ip` varchar(45) NOT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'login',
  `identificador` varchar(100) DEFAULT NULL,
  `fecha_intento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia`
--

CREATE TABLE `materia` (
  `id` int(11) NOT NULL,
  `codigo_materia` varchar(20) NOT NULL,
  `nombre_materia` varchar(100) NOT NULL,
  `semestre` int(11) DEFAULT NULL,
  `uc` int(11) DEFAULT 3,
  `orden` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materia`
--

INSERT INTO `materia` (`id`, `codigo_materia`, `nombre_materia`, `semestre`, `uc`, `orden`) VALUES
(1, 'FIL-001', 'Filosofía, Ética y Valores', 0, 3, 1),
(2, 'LEN-001', 'Lenguaje y Comunicación', 0, 3, 1),
(3, 'MAT-000', 'Matemática', 0, 3, 1),
(4, 'DIB-101', 'Dibujo', 1, 3, 1),
(5, 'EDA-101', 'Educación Ambiental', 1, 3, 1),
(6, 'GEO-101', 'Geometría Analítica', 1, 3, 1),
(7, 'HSC-101', 'Hombre, Sociedad, Ciencia y Tecnología', 1, 3, 1),
(8, 'ING-101', 'Inglés I', 1, 3, 1),
(9, 'MAT-101', 'Matemáticas I', 1, 3, 1),
(10, 'SEM-101', 'Seminario I', 1, 3, 1),
(11, 'ALG-201', 'Álgebra Lineal', 2, 3, 1),
(12, 'FIL-201', 'Física I Laboratorio', 2, 3, 1),
(13, 'FIS-201', 'Física I', 2, 3, 1),
(14, 'ING-201', 'Inglés II', 2, 3, 1),
(15, 'MAT-201', 'Matemáticas II', 2, 3, 1),
(16, 'QUI-201', 'Química General', 2, 3, 1),
(17, 'SEM-201', 'Seminario II', 2, 3, 1),
(18, 'FIS-301', 'Física II', 3, 3, 1),
(19, 'MAT-301', 'Matemáticas III', 3, 3, 1),
(20, 'PRO-301', 'Probabilidad y Estadística', 3, 3, 1),
(21, 'PRG-301', 'Programación (Pascal)', 3, 3, 1),
(22, 'SAD-301', 'Sistemas Administrativos', 3, 3, 1),
(23, 'CAL-401', 'Cálculo Numérico', 4, 3, 1),
(24, 'LP1-401', 'Lenguajes de Programación I (C/C++)', 4, 3, 1),
(25, 'LOG-401', 'Lógica Matemática', 4, 3, 1),
(26, 'PDA-401', 'Procesamiento de Datos', 4, 3, 1),
(27, 'SPR-401', 'Sistemas de Producción', 4, 3, 1),
(28, 'TSI-401', 'Teorías de Sistemas', 4, 3, 1),
(29, 'ASI-501', 'Análisis de Sistemas', 5, 3, 1),
(30, 'BD0-501', 'Base de Datos', 5, 3, 1),
(31, 'CBA-501', 'Cátedra Bolivariana I', 5, 3, 1),
(32, 'CIR-501', 'Circuitos Lógicos', 5, 3, 1),
(33, 'INO-501', 'Investigación de Operaciones', 5, 3, 1),
(34, 'LP2-501', 'Lenguajes de Programación II (Java)', 5, 3, 1),
(35, 'TGR-501', 'Teoría de Grafos', 5, 3, 1),
(36, 'ARC-601', 'Arquitectura del Computador', 6, 3, 1),
(37, 'CBB-601', 'Cátedra Bolivariana II', 6, 3, 1),
(38, 'DSI-601', 'Diseño de Sistemas', 6, 3, 1),
(39, 'LP3-601', 'Lenguaje de Programación III (HTML5, CSS3, PHP)', 6, 3, 1),
(40, 'ONL-601', 'Optimización No Lineal', 6, 3, 1),
(41, 'PES-601', 'Procesos Estocásticos', 6, 3, 1),
(42, 'SOP-601', 'Sistemas Operativos', 6, 3, 1),
(43, 'GIN-701', 'Gerencia de la Informática', 7, 3, 1),
(44, 'ISI-701', 'Implantación de Sistemas', 7, 3, 1),
(45, 'MIN-701', 'Metodología de la Investigación', 7, 3, 1),
(46, 'RED-701', 'Redes', 7, 3, 1),
(47, 'SIM-701', 'Simulación y Modelos', 7, 3, 1),
(48, 'AUS-801', 'Auditoría de Sistemas', 8, 3, 1),
(49, 'MLE-801', 'Marco Legal para el Ejercicio de la Ingeniería', 8, 3, 1),
(50, 'TEL-801', 'Teleprocesos', 8, 3, 1),
(51, 'TDE-801', 'Teoría de Decisiones', 8, 3, 1),
(52, 'ET-AS', 'Arquitectura de Software', NULL, 3, 1),
(53, 'ET-IA', 'Inteligencia Artificial', NULL, 3, 1),
(54, 'ET-RL', 'Redes de Área Local', NULL, 3, 1),
(55, 'ET-SABD', 'Sistemas Avanzados de Bases de Datos', NULL, 3, 1),
(56, 'ET-TR', 'Tecnología de Redes', NULL, 3, 1),
(57, 'ENT-DOI', 'Decisiones Óptimas de Inversión', NULL, 3, 1),
(58, 'ENT-GP', 'Gerencia de Proyectos', NULL, 3, 1),
(59, 'ENT-INF', 'Informática', NULL, 3, 1),
(60, 'ENT-IM', 'Ingeniería de Métodos', NULL, 3, 1),
(61, 'ENT-PG', 'Principios de Gerencia', NULL, 3, 1),
(62, 'DEF-000', 'Defensa Integral', 0, 2, 1),
(63, 'DEF-101', 'Defensa Integral I', 1, 2, 1),
(64, 'DEF-201', 'Defensa Integral II', 2, 2, 1),
(65, 'DEF-301', 'Defensa Integral III', 3, 2, 1),
(66, 'DEF-401', 'Defensa Integral IV', 4, 2, 1),
(67, 'DEF-501', 'Defensa Integral V', 5, 2, 1),
(68, 'DEF-601', 'Defensa Integral VI', 6, 2, 1),
(69, 'DEF-701', 'Defensa Integral VII', 7, 2, 1),
(70, 'DEF-801', 'Defensa Integral VIII', 8, 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfil`
--

CREATE TABLE `perfil` (
  `id` int(11) NOT NULL,
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
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `perfil`
--

INSERT INTO `perfil` (`id`, `cedula`, `tipo_documento`, `nombre`, `segundo_nombre`, `apellido`, `segundo_apellido`, `id_carrera`, `foto_perfil`, `fecha_carnetizacion`, `id_usuario`, `semestre_actual`, `direccion`, `telefono`) VALUES
(1, 'V-COORD', 'V', 'COORDINADORA', '', 'ACADEMICA', '', 1, 'default.svg', NULL, 1, 0, '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodo_academico`
--

CREATE TABLE `periodo_academico` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Planificado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodo_academico`
--

INSERT INTO `periodo_academico` (`id`, `nombre`, `fecha_inicio`, `fecha_fin`, `estado`, `created_at`) VALUES
(1, '2026-I', '2026-01-01', NULL, 'Activo', '2026-07-02 05:47:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_seguridad`
--

CREATE TABLE `preguntas_seguridad` (
  `id` int(11) NOT NULL,
  `texto_pregunta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `preguntas_seguridad`
--

INSERT INTO `preguntas_seguridad` (`id`, `texto_pregunta`) VALUES
(1, '¿En qué ciudad naciste?'),
(2, '¿Cuál es el primer nombre de tu madre?'),
(3, '¿Cómo se llamaba tu primer colegio?'),
(4, '¿Cómo se llamaba tu primera mascota?'),
(5, '¿Cuál es tu segundo nombre?'),
(6, '¿En qué mes es tu cumpleaños?'),
(7, '¿Cuál es tu fruta favorita?'),
(8, '¿Qué idioma hablas en casa?'),
(9, '¿Cuál es tu color favorito?');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prelacion`
--

CREATE TABLE `prelacion` (
  `id` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_prerrequisito` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `prelacion`
--

INSERT INTO `prelacion` (`id`, `id_materia`, `id_prerrequisito`) VALUES
(16, 13, 6),
(14, 14, 8),
(7, 15, 9),
(15, 17, 10),
(17, 18, 13),
(8, 19, 15),
(25, 20, 7),
(34, 22, 14),
(9, 23, 19),
(31, 24, 21),
(18, 25, 11),
(30, 26, 17),
(42, 27, 24),
(43, 27, 29),
(35, 28, 22),
(36, 29, 28),
(26, 30, 20),
(19, 32, 25),
(10, 33, 23),
(32, 34, 24),
(22, 35, 18),
(20, 36, 32),
(37, 38, 29),
(33, 39, 34),
(11, 40, 33),
(23, 41, 35),
(27, 42, 30),
(40, 43, 29),
(21, 43, 36),
(38, 44, 38),
(24, 45, 41),
(28, 46, 42),
(12, 47, 40),
(39, 48, 44),
(41, 49, 44),
(29, 50, 46),
(13, 51, 47);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_documentos`
--

CREATE TABLE `registro_documentos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `tipo` enum('Word','PDF','Imagen','Excel') NOT NULL DEFAULT 'PDF',
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas_seguridad_usuario`
--

CREATE TABLE `respuestas_seguridad_usuario` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_pregunta` int(11) NOT NULL,
  `hash_respuesta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion`
--

CREATE TABLE `seccion` (
  `id` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `nombre_seccion` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_inscripcion`
--

CREATE TABLE `solicitud_inscripcion` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `id_periodo` int(11) DEFAULT NULL,
  `estado` enum('Pendiente','Aceptada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `nota` int(11) DEFAULT NULL,
  `ciclo_cerrado` tinyint(1) NOT NULL DEFAULT 0,
  `valido_coordinador` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `id_perfil` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL,
  `estado` enum('Pendiente','Aprobado','Rechazado','Inactivo') NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `id_perfil`, `correo`, `clave`, `rol`, `estado`) VALUES
(1, 1, 'coordinadora@unefa.edu.ve', '$2y$10$S1.rBMWTk1J7RqNw.UL8qu3GBaeK4yh2PLO4yRQZw95eawUqh2qXi', 'Coordinador', 'Aprobado');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `fecha_hora` (`fecha_hora`);

--
-- Indices de la tabla `carrera`
--
ALTER TABLE `carrera`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_carrera` (`codigo_carrera`);

--
-- Indices de la tabla `cronograma_evento`
--
ALTER TABLE `cronograma_evento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anio` (`anio`),
  ADD KEY `categoria` (`categoria`),
  ADD KEY `id_periodo` (`id_periodo`);

--
-- Indices de la tabla `intentos_fallidos`
--
ALTER TABLE `intentos_fallidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `direccion_ip` (`direccion_ip`),
  ADD KEY `tipo` (`tipo`),
  ADD KEY `fecha_intento` (`fecha_intento`);

--
-- Indices de la tabla `materia`
--
ALTER TABLE `materia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_materia` (`codigo_materia`);

--
-- Indices de la tabla `perfil`
--
ALTER TABLE `perfil`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_carrera` (`id_carrera`),
  ADD KEY `perfil_ibfk_usuario` (`id_usuario`);

--
-- Indices de la tabla `periodo_academico`
--
ALTER TABLE `periodo_academico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `preguntas_seguridad`
--
ALTER TABLE `preguntas_seguridad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `prelacion`
--
ALTER TABLE `prelacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_prelacion` (`id_materia`,`id_prerrequisito`),
  ADD KEY `id_prerrequisito` (`id_prerrequisito`);

--
-- Indices de la tabla `registro_documentos`
--
ALTER TABLE `registro_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `respuestas_seguridad_usuario`
--
ALTER TABLE `respuestas_seguridad_usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `respuestas_seguridad_usuario_ibfk_1` (`id_usuario`),
  ADD KEY `respuestas_seguridad_usuario_ibfk_2` (`id_pregunta`);

--
-- Indices de la tabla `seccion`
--
ALTER TABLE `seccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_materia` (`id_materia`),
  ADD KEY `id_docente` (`id_docente`);

--
-- Indices de la tabla `solicitud_inscripcion`
--
ALTER TABLE `solicitud_inscripcion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_estudiante` (`id_estudiante`),
  ADD KEY `id_seccion` (`id_seccion`),
  ADD KEY `solicitud_inscripcion_ibfk_3` (`id_periodo`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_perfil` (`id_perfil`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carrera`
--
ALTER TABLE `carrera`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `cronograma_evento`
--
ALTER TABLE `cronograma_evento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `intentos_fallidos`
--
ALTER TABLE `intentos_fallidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materia`
--
ALTER TABLE `materia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `perfil`
--
ALTER TABLE `perfil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `periodo_academico`
--
ALTER TABLE `periodo_academico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `preguntas_seguridad`
--
ALTER TABLE `preguntas_seguridad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `prelacion`
--
ALTER TABLE `prelacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `registro_documentos`
--
ALTER TABLE `registro_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `respuestas_seguridad_usuario`
--
ALTER TABLE `respuestas_seguridad_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seccion`
--
ALTER TABLE `seccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitud_inscripcion`
--
ALTER TABLE `solicitud_inscripcion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `perfil`
--
ALTER TABLE `perfil`
  ADD CONSTRAINT `perfil_ibfk_1` FOREIGN KEY (`id_carrera`) REFERENCES `carrera` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `perfil_ibfk_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `prelacion`
--
ALTER TABLE `prelacion`
  ADD CONSTRAINT `prelacion_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prelacion_ibfk_2` FOREIGN KEY (`id_prerrequisito`) REFERENCES `materia` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `registro_documentos`
--
ALTER TABLE `registro_documentos`
  ADD CONSTRAINT `registro_documentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `respuestas_seguridad_usuario`
--
ALTER TABLE `respuestas_seguridad_usuario`
  ADD CONSTRAINT `respuestas_seguridad_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `respuestas_seguridad_usuario_ibfk_2` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas_seguridad` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seccion`
--
ALTER TABLE `seccion`
  ADD CONSTRAINT `seccion_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seccion_ibfk_2` FOREIGN KEY (`id_docente`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitud_inscripcion`
--
ALTER TABLE `solicitud_inscripcion`
  ADD CONSTRAINT `solicitud_inscripcion_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitud_inscripcion_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitud_inscripcion_ibfk_3` FOREIGN KEY (`id_periodo`) REFERENCES `periodo_academico` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_perfil`) REFERENCES `perfil` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
