<?php

require_once __DIR__ . '/Conexion.php';

class UsuarioCliente extends Conexion
{
    public function buscarPorEmail($email)
    {
        $sql = "SELECT * FROM usuarios_clientes WHERE email = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function crear($data)
    {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios_clientes
                (nombre, apellido, email, telefono, password)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "sssss",
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $data['telefono'],
            $passwordHash
        );

        $stmt->execute();

        return $this->db->insert_id;
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