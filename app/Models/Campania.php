<?php

require_once __DIR__ . '/Conexion.php';

class Campania extends Conexion
{
    /** Crea una campaña con un código único a partir del nombre. Devuelve el código. */
    public function crear(string $nombre, string $destino): string
    {
        $base   = mb_substr(generarSlug($nombre), 0, 34) ?: 'campania';
        $codigo = $base;
        $n      = 2;

        while ($this->existeCodigo($codigo)) {
            $codigo = $base . '-' . $n++;
        }

        $stmt = $this->db->prepare("INSERT INTO campanias (codigo, nombre, url_destino) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $codigo, $nombre, $destino);
        $stmt->execute();

        return $codigo;
    }

    /** Borra la campaña. Las visitas y pedidos que trajo conservan su código. */
    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM campanias WHERE id_campania = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    private function existeCodigo(string $codigo): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM campanias WHERE codigo = ? LIMIT 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    /** Link completo para compartir: destino + ?c=codigo */
    public static function link(array $c): string
    {
        $destino = $c['url_destino'] ?: '/tienda';
        return url_absoluta(ltrim($destino, '/')) . (strpos($destino, '?') !== false ? '&' : '?') . 'c=' . $c['codigo'];
    }
}