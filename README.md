# SICEU UNEFA

**Sistema de Control de Estudios Universitarios** - Universidad Nacional Experimental Politecnica de la Fuerza Armada (UNEFA), Nucleo Guacara.

Aplicacion web monolitica para la gestion academica integral del programa de **Ingenieria de Sistemas**. Cubre el ciclo de vida completo del estudiante: desde el pre-registro en linea hasta la carnetizacion digital, pasando por carga documental, inscripcion de materias con validacion de prelaciones, gestion de notas, cierre de ciclos y generacion de reportes oficiales.

---

## Stack Tecnologico

| Tecnologia | Proposito |
|---|---|
| **PHP 8+** | Backend - arquitectura Front Controller sin framework |
| **MySQL / MariaDB** | Base de datos relacional via PDO |
| **Bootstrap 5** | Framework CSS / JS |
| **FPDF** | Generacion de reportes PDF |
| **JavaScript (Fetch API)** | Interacciones AJAX |
| **HTML5 / CSS3** | Frontend con sistema de diseño propio (2600+ lineas de CSS) |

---

## Arquitectura

```
index.php                     ← Front Controller (unico punto de entrada)
├── app/
│   ├── core/Database.php     ← Conexion PDO + auto-creacion de esquema
│   ├── controllers/          ← Logica de negocio (AuthController)
│   ├── helpers/              ← AuditHelper, ReportesPDF (FPDF)
│   ├── models/               ← Capa de datos (Usuario)
│   └── views/                ← Vistas organizadas por modulo
│       ├── auth/             ← Login, registro, documentos
│       ├── dashboard/        ← Paneles por rol
│       ├── home/             ← Landing page
│       ├── coordinator/      ← Gestion administrativa
│       ├── student/          ← Carnet, QR, record
│       ├── teacher/          ← Carnet docente
│       ├── profile/          ← Perfil de usuario
│       └── reports/          ← Auditoria
├── database/unefa_siceu_db.sql
├── libs/fpdf.php             ← Libreria FPDF
└── public/                   ← Assets, uploads, carnets, CSS, JS
```

## Flujo del Sistema

```
Pre-registro → Carga de Documentos → Aprobacion (Coordinador)
    → Inscripcion de Materias (con validacion de prelaciones)
    → Gestion de Notas (Docente) → Cierre de Ciclo (Docente)
    → Validacion de Ciclo (Coordinador) → Avance de Semestre
    → Carnetizacion Digital con QR
```

---

## Roles de Usuario

### Estudiante
- Pre-registro con seleccion de preguntas de seguridad
- Carga y reemplazo de documentos requeridos
- Inscripcion de materias por semestre con validacion de prelaciones y limite de 18 UC
- Visualizacion de carnet digital con QR, record academico, constancias PDF

### Docente
- Aprobacion/rechazo de solicitudes de inscripcion
- Registro de notas (escala 0–20, aprobatorio ≥ 10)
- Cierre de ciclo por seccion
- Descarga de reportes de notas en PDF

### Coordinador
- Aprobacion documental con vista previa y motivos de rechazo
- Gestion de docentes (registro, asignacion a materias, reasignacion)
- Validacion de ciclos cerrados (avance de semestre)
- Carnetizacion con captura webcam y simulacion de vencimiento
- CRUD de periodos academicos y cronograma de eventos
- Reportes PDF (estudiantes, docentes, bitacora)
- Activacion/desactivacion de cuentas de usuario

---

## Modulos Principales

| Modulo | Descripcion |
|---|---|
| **Autenticacion y Seguridad** | Login con roles, proteccion CSRF, limite de intentos, contraseñas encriptadas |
| **Registro** | Registro de estudiantes con preguntas de seguridad |
| **Carga Documental** | Subir PDF/Word/imagenes, validar tipo de archivo, ver y reemplazar documentos |
| **Dashboard Coordinador** | Aprobar o rechazar registros, ver progreso de documentos |
| **Inscripcion de Materias** | Ver materias por semestre, validar requisitos, limite de 18 UC |
| **Gestion de Notas** | Ingresar notas, ver progreso, cerrar ciclo |
| **Validacion de Ciclos** | Aprobar o devolver ciclos cerrados por el docente |
| **Carnetizacion** | Tomar foto con webcam, generar QR, simular vencimiento |
| **Periodos Academicos** | Crear, activar y finalizar periodos |
| **Cronograma** | Eventos del calendario, copiar entre años |
| **Auditoria** | Bitacora de acciones con exportacion a PDF |
| **Reportes PDF** | Constancias, listados de estudiantes/docentes, actas de notas |
| **Landing Page** | Pagina principal con banners, videos, enlaces utiles |

---

## Base de Datos

**Motor:** MySQL / MariaDB - 14 tablas:

| Tabla | Proposito |
|---|---|
| `usuario` | Cuentas de usuario con rol y estado |
| `perfil` | Datos personales, foto, semestre |
| `carrera` | Carreras disponibles |
| `materia` | Materias del pensum con codigo y creditos |
| `prelacion` | Requisitos entre materias |
| `seccion` | Secciones asignadas a docentes |
| `solicitud_inscripcion` | Inscripciones con notas y estado |
| `registro_documentos` | Documentos subidos por los usuarios |
| `preguntas_seguridad` | Preguntas para recuperar contraseña |
| `respuestas_seguridad_usuario` | Respuestas guardadas de cada usuario |
| `bitacora` | Registro de acciones en el sistema |
| `intentos_fallidos` | Control de intentos de login por IP |
| `periodo_academico` | Periodos con fechas y estado |
| `cronograma_evento` | Eventos del calendario |

---

## Seguridad

- **CSRF**: Token en sesion validado en todo POST
- **Rate Limiting**: 5 intentos fallidos por 15 minutos por IP (login y reset de contraseña)
- **Hash de contraseñas**: `PASSWORD_BCRYPT`
- **Prepared Statements**: Consultas parametrizadas via PDO
- **Validacion de Archivos**: MIME type con `finfo`, extension, tamaño maximo 10 MB
- **Control de Acceso**: Verificacion de rol en cada pagina/accion
- **Cabeceras HTTP**: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`

---

## Instalacion Local

1. **Requisitos**
   - PHP 8.0+
   - MySQL 5.7+ / MariaDB 10+
   - Servidor web (Apache / Nginx)

2. **Clonar el repositorio**
   ```bash
   git clone https://github.com/tu-usuario/siceu-unefa.git
   ```

3. **Configurar base de datos**
   - La conexion se configura en `app/core/Database.php`:
     ```php
     host=localhost, db=unefa_siceu_db, user=root, password=""
     ```
   - Las tablas se crean automaticamente al primer acceso.

4. **Cargar datos iniciales (seeders)**
   - Ejecutar `database/unefa_siceu_db.sql` para poblar carreras, materias, prelaciones y usuario coordinador por defecto.

5. **Acceso**
   - Coordinador por defecto: `coordinadora@unefa.edu.ve` / `12345678`
   - Ubicar el proyecto en la raiz del servidor web (`www/` o `htdocs/`).

## Equipo de Desarrollo

| Integrante | Rol | Cedula |
|---|---|---|
| **Adrian Bello** | Technical Product Manager & Data Architect | V-31.932.406 |
| **Jesus Parra** | Backend Engineer | V-29.618.152 |
| **Cristian Trosel** | UX/UI Designer | V-31.268.941 |

---

## Licencia

© 2026 Equipo de Desarrollo SICEU UNEFA. Todos los derechos reservados.

Desarrollado por **Adrian Bello**, **Jesus Parra** y **Cristian Trosel** como proyecto academico de Ingenieria de Sistemas.
