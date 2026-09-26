<?php

require_once __DIR__ . '/Conexion.php';

/**
 * Límite de intentos de login (admin y clientes).
 * Regla: MAX_INTENTOS fallidos dentro de VENTANA_MIN minutos → bloqueo por VENTANA_MIN minutos.
 * Se evalúa por IP y por email; alcanza con que uno de los dos llegue al límite.
 * Un login exitoso "resetea" el contador de esa IP y de ese email.
 */
class LoginIntento extends Conexion
{
    public const MAX_INTENTOS = 5;
    public const VENTANA_MIN  = 15;

    /** IP del visitante (REMOTE_ADDR: no se confía en encabezados que manda el cliente). */
    public static function ip(): string
    {
        return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
    }

    /**
     * Devuelve los MINUTOS que faltan para desbloquear, o 0 si no está bloqueado.
     */
    public function minutosBloqueo(string $tipo, string $email, string $ip): int
    {
        $email = $this->normalizar($email);

        $segundos = max(
            $this->segundosRestantes($tipo, 'ip', $ip),
            $email !== '' ? $this->segundosRestantes($tipo, 'email', $email) : 0
        );

        return $segundos > 0 ? (int) ceil($segundos / 60) : 0;
    }

    public function registrar(string $tipo, string $email, string $ip, bool $exitoso): void
    {
        $email   = $this->normalizar($email);
        $exitoso = $exitoso ? 1 : 0;

        $stmt = $this->db->prepare(
            "INSERT INTO login_intentos (tipo, email, ip, exitoso) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssi", $tipo, $email, $ip, $exitoso);
        $stmt->execute();

        // De vez en cuando, limpiar registros viejos (1 de cada 50 intentos)
        if (random_int(1, 50) === 1) {
            $this->db->query("DELETE FROM login_intentos WHERE fecha < NOW() - INTERVAL 30 DAY");
        }
    }

    /**
     * Segundos de bloqueo restantes para una IP o un email.
     * Cuenta solo los fallos posteriores al último login exitoso.
     */
    private function segundosRestantes(string $tipo, string $campo, string $valor): int
    {
        // $campo viene de este mismo archivo ('ip' o 'email'), nunca del usuario
        $campo = $campo === 'email' ? 'email' : 'ip';
        $ventana = self::VENTANA_MIN;

        $sql = "SELECT
                    COUNT(*) AS fallos,
                    GREATEST(0, ? * 60 - TIMESTAMPDIFF(SECOND, MAX(fecha), NOW())) AS restante
                FROM login_intentos
                WHERE tipo = ?
                AND $campo = ?
                AND exitoso = 0
                AND fecha > NOW() - INTERVAL ? MINUTE
                AND fecha > COALESCE(
                    (SELECT MAX(fecha) FROM login_intentos
                     WHERE tipo = ? AND $campo = ? AND exitoso = 1),
                    '1970-01-01'
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ississ", $ventana, $tipo, $valor, $ventana, $tipo, $valor);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ((int) $row['fallos'] < self::MAX_INTENTOS) {
            return 0;
        }

        return (int) $row['restante'];
    }

    private function normalizar(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}