<?php
// REPORTES PDF
require_once __DIR__ . '/../../libs/fpdf.php';

class ReportesPDF extends FPDF {
    protected $titulo_reporte = '';

    private function u($str) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str ?? '');
    }

    public function Header() {
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, $this->u('REPÃšBLICA BOLIVARIANA DE VENEZUELA'), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->u('UNIVERSIDAD NACIONAL EXPERIMENTAL POLITÃ‰CNICA DE LA FUERZA ARMADA'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->u('UNEFA - NÃšCLEO GUÃCARA'), 0, 1, 'C');
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 8, $this->u($this->titulo_reporte), 0, 1, 'C');
        $this->Ln(2);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, $this->u('PÃ¡gina ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    public function setTitulo($titulo) {
        $this->titulo_reporte = $titulo;
    }

    private function th($w, $label, $size = 9) {
        $this->SetFont('Arial', 'B', $size);
        $this->SetFillColor(0, 51, 102);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($w, 7, $this->u($label), 1, 0, 'C', true);
    }

    private function td($w, $text, $size = 8, $align = 'C', $max = 50) {
        $this->SetFont('Arial', '', $size);
        $this->SetTextColor(40, 40, 40);
        $this->Cell($w, 5.5, $this->u(mb_substr($text, 0, $max, 'UTF-8')), 1, 0, $align);
    }

    private function section($label) {
        if ($this->GetY() > 210) $this->AddPage();
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(220, 230, 241);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 7, $this->u('  ' . $label), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);
    }

    private function headerTableEstudiantes() {
        $this->th(7, '#');
        $this->th(68, 'NOMBRE COMPLETO', 8);
        $this->th(24, 'CÃ‰DULA');
        $this->th(10, 'TELF');
        $this->th(40, 'MATERIA', 7);
        $this->th(17, 'SECCIÃ“N', 7);
        $this->th(54, 'CORREO', 7);
        $this->th(18, 'CARRERA', 7);
        $this->Ln();
    }

    public function reporteEstudiantes($conexion) {
        $this->AliasNbPages();
        $this->setTitulo('REPORTE DE ESTUDIANTES');
        $this->AddPage();

        $stmt = $conexion->query(
            "SELECT u.id, p.cedula, p.tipo_documento, p.nombre, p.segundo_nombre,
                    p.apellido, p.segundo_apellido, p.telefono, u.correo,
                    m.nombre_materia, m.semestre, s.nombre_seccion,
                    c.nombre_carrera
             FROM usuario u
             JOIN perfil p ON u.id_perfil = p.id
             LEFT JOIN carrera c ON p.id_carrera = c.id
             LEFT JOIN solicitud_inscripcion si ON si.id_estudiante = u.id AND si.estado = 'Aceptada'
             LEFT JOIN seccion s ON si.id_seccion = s.id
             LEFT JOIN materia m ON s.id_materia = m.id
             WHERE u.rol = 'Estudiante' AND u.estado = 'Aprobado'
             ORDER BY m.semestre, m.nombre_materia, p.apellido, p.nombre"
        );
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($estudiantes)) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, $this->u('No hay estudiantes registrados.'), 0, 1, 'C');
            return $this->Output('S');
        }

        $semestre_actual = null;
        $i = 0;
        foreach ($estudiantes as $e) {
            $sem = $e['semestre'] ?? 'S/S';
            if ($sem !== $semestre_actual) {
                $semestre_actual = $sem;
                $this->section($sem !== 'S/S' ? "SEMESTRE $sem" : 'SIN SEMESTRE');
                $this->headerTableEstudiantes();
                $i = 0;
            }
            if ($this->GetY() > 192) { $this->AddPage(); $this->headerTableEstudiantes(); }
            $i++;
            $fill = $i % 2 === 0;
            $nombre = trim("{$e['apellido']} {$e['segundo_apellido']}, {$e['nombre']} {$e['segundo_nombre']}");
            $cedula = $e['tipo_documento'] . '-' . $e['cedula'];
            $telf = $e['telefono'] ?? '-';

            if ($fill) $this->SetFillColor(240, 245, 250);
            else $this->SetFillColor(255, 255, 255);

            $this->td(7, $e['id'], 8, 'C', 4);
            $this->td(68, $nombre, 7.5, 'L', 40);
            $this->td(24, $cedula, 8, 'C', 15);
            $this->td(10, $telf, 7, 'C', 8);
            $this->td(40, $e['nombre_materia'] ?? '-', 7, 'L', 24);
            $this->td(17, $e['nombre_seccion'] ?? '-', 7, 'C', 12);
            $this->td(54, $e['correo'], 7, 'L', 36);
            $this->td(18, $e['nombre_carrera'] ?? '-', 7, 'C', 12);
            $this->Ln();
        }
        return $this->Output('S');
    }

    private function headerTableDocentes() {
        $this->th(7, '#');
        $this->th(72, 'NOMBRE COMPLETO', 8);
        $this->th(24, 'CÃ‰DULA');
        $this->th(22, 'TELÃ‰FONO', 7);
        $this->th(44, 'MATERIA', 7);
        $this->th(18, 'SECCIÃ“N', 7);
        $this->th(52, 'CORREO', 7);
        $this->Ln();
    }

    public function reporteDocentes($conexion) {
        $this->AliasNbPages();
        $this->setTitulo('REPORTE DE DOCENTES');
        $this->AddPage();

        $stmt = $conexion->query(
            "SELECT u.id, p.cedula, p.tipo_documento, p.nombre, p.segundo_nombre,
                    p.apellido, p.segundo_apellido, p.telefono, u.correo,
                    m.nombre_materia, s.nombre_seccion
             FROM usuario u
             JOIN perfil p ON u.id_perfil = p.id
             LEFT JOIN seccion s ON s.id_docente = u.id
             LEFT JOIN materia m ON s.id_materia = m.id
             WHERE u.rol = 'Docente'
             ORDER BY p.apellido, p.nombre"
        );
        $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($docentes)) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, $this->u('No hay docentes registrados.'), 0, 1, 'C');
            return $this->Output('S');
        }

        $this->headerTableDocentes();
        $i = 0;
        foreach ($docentes as $d) {
            if ($this->GetY() > 225) { $this->AddPage(); $this->headerTableDocentes(); }
            $i++;
            $fill = $i % 2 === 0;
            $nombre = trim("{$d['apellido']} {$d['segundo_apellido']}, {$d['nombre']} {$d['segundo_nombre']}");
            $cedula = $d['tipo_documento'] . '-' . $d['cedula'];

            if ($fill) $this->SetFillColor(240, 245, 250);
            else $this->SetFillColor(255, 255, 255);

            $this->td(7, $d['id'], 8, 'C', 4);
            $this->td(72, $nombre, 7.5, 'L', 42);
            $this->td(24, $cedula, 8, 'C', 15);
            $this->td(22, $d['telefono'] ?? '-', 8, 'C', 12);
            $this->td(44, $d['nombre_materia'] ?? '-', 7, 'L', 26);
            $this->td(18, $d['nombre_seccion'] ?? '-', 7, 'C', 12);
            $this->td(52, $d['correo'], 7, 'L', 36);
            $this->Ln();
        }
        return $this->Output('S');
    }

    private function headerTableBitacora() {
        $this->th(32, 'FECHA / HORA');
        $this->th(22, 'ROL');
        $this->th(65, 'USUARIO', 7);
        $this->th(40, 'ACCIÃ“N', 8);
        $this->th(76, 'DETALLE', 7);
        $this->th(22, 'IP');
        $this->Ln();
    }

    // CONSTANCIA DE ESTUDIO
    public function constanciaEstudio($conexion, $id_usuario) {
        $this->AliasNbPages();
        $this->setTitulo('CONSTANCIA DE ESTUDIO');
        $this->AddPage('P');

        $stmt = $conexion->prepare(
            "SELECT u.correo, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido,
                    p.cedula, p.tipo_documento, p.semestre_actual, c.nombre_carrera
             FROM usuario u
             JOIN perfil p ON u.id_perfil = p.id
             LEFT JOIN carrera c ON p.id_carrera = c.id
             WHERE u.id = :id AND u.rol = 'Estudiante' AND u.estado = 'Aprobado'
             LIMIT 1"
        );
        $stmt->execute([':id' => $id_usuario]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, $this->u('Datos no encontrados.'), 0, 1, 'C');
            return $this->Output('S');
        }

        $this->Ln(8);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 6, $this->u(
            'Quien suscribe, CoordinaciÃ³n AcadÃ©mica de la Universidad Nacional Experimental PolitÃ©cnica de la Fuerza Armada (UNEFA) - NÃºcleo GuÃ¡cara, hace constar que:'
        ), 0, 'J');
        $this->Ln(4);

        $nombre_completo = trim("{$datos['nombre']} {$datos['segundo_nombre']} {$datos['apellido']} {$datos['segundo_apellido']}");
        $cedula_completa = ($datos['tipo_documento'] ?? 'V') . '-' . $datos['cedula'];

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, $this->u($nombre_completo), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $this->u("Titular de la CÃ©dula de Identidad NÂ° $cedula_completa"), 0, 1, 'C');
        $this->Ln(4);

        $this->MultiCell(0, 6, $this->u(
            "Es estudiante regular de esta casa de estudios, cursando la carrera de {$datos['nombre_carrera']} " .
            "en el " . labelSemestre($datos['semestre_actual'] ?? 0) . " semestre del Plan de Estudios vigente."
        ), 0, 'J');
        $this->Ln(4);

        $this->MultiCell(0, 6, $this->u(
            'Se expide la presente constancia a solicitud de la parte interesada, para los fines que considere pertinentes.'
        ), 0, 'J');
        $this->Ln(12);

        $this->Cell(0, 6, $this->u('GuÃ¡cara, ' . date('d') . ' de ' . $this->mesEspanol(date('m')) . ' de ' . date('Y')), 0, 1, 'R');
        $this->Ln(20);
        $this->Cell(0, 6, $this->u('___________________________________'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->u('CoordinaciÃ³n AcadÃ©mica UNEFA - GuÃ¡cara'), 0, 1, 'C');

        return $this->Output('S');
    }

    // CONSTANCIA DE INSCRIPCION
    public function constanciaInscripcion($conexion, $id_usuario) {
        $this->AliasNbPages();
        $this->setTitulo('CONSTANCIA DE INSCRIPCIÃ“N');
        $this->AddPage('P');

        $stmt = $conexion->prepare(
            "SELECT u.correo, p.nombre, p.segundo_nombre, p.apellido, p.segundo_apellido,
                    p.cedula, p.tipo_documento, p.semestre_actual, c.nombre_carrera
             FROM usuario u
             JOIN perfil p ON u.id_perfil = p.id
             LEFT JOIN carrera c ON p.id_carrera = c.id
             WHERE u.id = :id AND u.rol = 'Estudiante' AND u.estado = 'Aprobado'
             LIMIT 1"
        );
        $stmt->execute([':id' => $id_usuario]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, $this->u('Datos no encontrados.'), 0, 1, 'C');
            return $this->Output('S');
        }

        $stmt_mat = $conexion->prepare(
            "SELECT m.nombre_materia, m.codigo_materia, m.uc, s.nombre_seccion, si.estado
             FROM solicitud_inscripcion si
             JOIN seccion s ON si.id_seccion = s.id
             JOIN materia m ON s.id_materia = m.id
             WHERE si.id_estudiante = :id AND (si.estado = 'Aceptada' OR si.estado = 'Pendiente')"
        );
        $stmt_mat->execute([':id' => $id_usuario]);
        $materias = $stmt_mat->fetchAll(PDO::FETCH_ASSOC);

        $nombre_completo = trim("{$datos['nombre']} {$datos['segundo_nombre']} {$datos['apellido']} {$datos['segundo_apellido']}");
        $cedula_completa = ($datos['tipo_documento'] ?? 'V') . '-' . $datos['cedula'];

        $this->Ln(8);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 6, $this->u(
            'Por medio de la presente, se deja constancia que el (la) ciudadano(a):'
        ), 0, 'J');
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, $this->u($nombre_completo), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $this->u("C.I. NÂ° $cedula_completa"), 0, 1, 'C');
        $this->Ln(3);

        $this->Cell(0, 6, $this->u("Carrera: {$datos['nombre_carrera']}"), 0, 1, 'L');
        $this->Cell(0, 6, $this->u('Semestre: ' . labelSemestre($datos['semestre_actual'] ?? 0)), 0, 1, 'L');
        $this->Ln(3);
        $this->Cell(0, 6, $this->u('Se encuentra inscrito(a) en las siguientes materias:'), 0, 1, 'L');
        $this->Ln(2);

        $this->th(10, 'NÂ°', 9);
        $this->th(65, 'MATERIA', 9);
        $this->th(25, 'CÃ“DIGO', 9);
        $this->th(15, 'UC', 9);
        $this->th(35, 'SECCIÃ“N', 9);
        $this->th(30, 'ESTADO', 9);
        $this->Ln();

        $i = 0;
        $total_uc = 0;
        foreach ($materias as $m) {
            $i++;
            $total_uc += intval($m['uc']);
            if ($this->GetY() > 235) $this->AddPage();
            $fill = $i % 2 === 0;
            if ($fill) $this->SetFillColor(240, 245, 250);
            else $this->SetFillColor(255, 255, 255);
            $this->td(10, (string)$i, 9, 'C', 3);
            $this->td(65, $m['nombre_materia'], 8, 'L', 40);
            $this->td(25, $m['codigo_materia'], 8, 'C', 12);
            $this->td(15, (string)$m['uc'], 8, 'C', 4);
            $this->td(35, $m['nombre_seccion'], 8, 'C', 15);
            $this->td(30, $m['estado'], 8, 'C', 12);
            $this->Ln();
        }

        $this->Ln(2);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->u("Total de Unidades de CrÃ©dito inscritas: $total_uc"), 0, 1, 'R');
        $this->Ln(10);

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $this->u('GuÃ¡cara, ' . date('d') . ' de ' . $this->mesEspanol(date('m')) . ' de ' . date('Y')), 0, 1, 'R');
        $this->Ln(20);
        $this->Cell(0, 6, $this->u('___________________________________'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->u('CoordinaciÃ³n AcadÃ©mica UNEFA - GuÃ¡cara'), 0, 1, 'C');

        return $this->Output('S');
    }

    private function mesEspanol($mes) {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return $meses[intval($mes)] ?? $mes;
    }

    public function reporteBitacora($conexion, $limite = 300) {
        $this->AliasNbPages();
        $this->setTitulo('BITÃCORA DE ACTIVIDAD (Ãšltimos ' . $limite . ' registros)');
        $this->AddPage();

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
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($registros)) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, $this->u('No hay registros de actividad.'), 0, 1, 'C');
            return $this->Output('S');
        }

        $this->headerTableBitacora();
        $i = 0;
        foreach ($registros as $r) {
            if ($this->GetY() > 225) { $this->AddPage(); $this->headerTableBitacora(); }
            $i++;
            $fill = $i % 2 === 0;
            $fecha = date('d/m/Y H:i', strtotime($r['fecha_hora']));
            $usuario = $r['correo'] ?? 'Sistema';

            if ($fill) $this->SetFillColor(240, 245, 250);
            else $this->SetFillColor(255, 255, 255);

            $this->td(32, $fecha, 7, 'C', 18);
            $this->td(22, $r['rol'] ?? '-', 7, 'C', 14);
            $this->td(65, $usuario, 7, 'L', 40);
            $this->td(40, $r['accion'], 7, 'L', 30);
            $this->td(76, $r['detalle'] ?? '-', 7, 'L', 50);
            $this->td(22, $r['direccion_ip'], 7, 'C', 15);
            $this->Ln();
        }
        return $this->Output('S');
    }
}

