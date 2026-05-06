<?php
 
require_once __DIR__ . '/Conexion.php';
 
class UsuarioAdmin extends Conexion
{
    public function buscarPorEmail($email)
    {
        $sql = "SELECT * FROM usuarios_admin WHERE email = ? LIMIT 1";
 
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
 
        return $stmt->get_result()->fetch_assoc();
    }
 
    public function validarLogin($email, $password)
    {
        $usuario = $this->buscarPorEmail($email);
 
        if (!$usuario) {
            return false;
        }
 
        if ($usuario['activo'] != 1) {
            return false;
        }
 
        if (!password_verify($password, $usuario['password'])) {
            return false;
        }
 
        return $usuario;
    }
}
 