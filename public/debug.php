<?php
require_once __DIR__ . '/../app/Models/Conexion.php';
require_once __DIR__ . '/../app/Models/UsuarioAdmin.php';

$model = new UsuarioAdmin();
$usuario = $model->buscarPorEmail('matilamelza@gmail.com');

var_dump($usuario);

// Si encontró el usuario, probamos el password
if ($usuario) {
    var_dump(password_verify('admin123', $usuario['password']));
}