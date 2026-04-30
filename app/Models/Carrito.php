<?php

require_once __DIR__ . '/Conexion.php';

class Carrito extends Conexion
{
    public function buscarVarianteDetalle($id_variante)
    {
        $sql = "SELECT 
                    pv.*,
                    p.nombre AS producto,
                    p.precio_base,
                    p.id_producto,
                    t.nombre AS talle,
                    c.nombre AS color,
                    (
                        SELECT pf.imagen 
                        FROM producto_fotos pf 
                        WHERE pf.id_producto = p.id_producto 
                        ORDER BY pf.principal DESC, pf.id_foto ASC 
                        LIMIT 1
                    ) AS foto
                FROM producto_variantes pv
                INNER JOIN productos p ON p.id_producto = pv.id_producto
                LEFT JOIN talles t ON t.id_talle = pv.id_talle
                LEFT JOIN colores c ON c.id_color = pv.id_color
                WHERE pv.id_variante = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_variante);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}