<?php

require_once __DIR__ . '/Conexion.php';

class Evento extends Conexion
{
    public function registrar(array $d): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO eventos (visitante, tipo, id_producto, id_variante, talle, con_stock, cantidad, campania)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssiisiis",
            $d['visitante'],
            $d['tipo'],
            $d['id_producto'],
            $d['id_variante'],
            $d['talle'],
            $d['con_stock'],
            $d['cantidad'],
            $d['campania']
        );
        $stmt->execute();
    }
}