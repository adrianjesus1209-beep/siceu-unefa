<?php
// ENRUTADOR DE DASHBOARD
if (!isset($_SESSION['rol_usuario'])) {
    header('Location: index.php?page=login');
    exit;
}

$rol = $_SESSION['rol_usuario'];

switch ($rol) {
    case 'Docente':
        require_once 'app/views/dashboard/teacher.view.php';
        break;
    case 'Estudiante':
        require_once 'app/views/dashboard/student.view.php';
        break;
    case 'Coordinador':
        require_once 'app/views/dashboard/simple.view.php';
        break;
    default:
        header('Location: index.php?page=home');
        exit;
}
