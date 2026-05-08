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

    /**
     * Crea un token de reset y lo guarda en la BD.
     * Expira en 1 hora. Devuelve el token generado.
     */
    public function crearTokenReset(string $email): ?string
    {
        // Verificar que el email exista
        $usuario = $this->buscarPorEmail($email);
        if (!$usuario) {
            return null;
        }
 
        // Invalidar tokens anteriores para ese email
        $stmt = $this->db->prepare(
            "UPDATE password_resets SET usado = 1 WHERE email = ?"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
 
        // Generar token seguro
        $token    = bin2hex(random_bytes(32));
        $expira   = date('Y-m-d H:i:s', strtotime('+1 hour'));
 
        $stmt = $this->db->prepare(
            "INSERT INTO password_resets (email, token, expira_en) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('sss', $email, $token, $expira);
        $stmt->execute();
 
        return $token;
    }
 
    /**
     * Valida un token: que exista, no esté usado y no haya expirado.
     * Devuelve el email asociado o null si es inválido.
     */
    public function validarToken(string $token): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT email FROM password_resets
             WHERE token = ? AND usado = 0 AND expira_en > NOW()
             LIMIT 1"
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
 
        return $row ? $row['email'] : null;
    }
 
    /**
     * Cambia la contraseña del usuario y marca el token como usado.
     */
    public function resetearPassword(string $token, string $nuevaPassword): bool
    {
        $email = $this->validarToken($token);
        if (!$email) {
            return false;
        }
 
        $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
 
        // Actualizar contraseña
        $stmt = $this->db->prepare(
            "UPDATE usuarios_clientes SET password = ? WHERE email = ?"
        );
        $stmt->bind_param('ss', $hash, $email);
        $stmt->execute();
 
        // Marcar token como usado
        $stmt = $this->db->prepare(
            "UPDATE password_resets SET usado = 1 WHERE token = ?"
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
 
        return true;
    }
}