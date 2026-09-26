<?php

require_once __DIR__ . '/Conexion.php';

class Visita extends Conexion
{
    public function registrar(array $d): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO visitas (visitante, tipo, id_ref, termino, resultados, origen, dispositivo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssisiss",
            $d['visitante'],
            $d['tipo'],
            $d['id_ref'],
            $d['termino'],
            $d['resultados'],
            $d['origen'],
            $d['dispositivo']
        );
        $stmt->execute();
    }

    /** Borra visitas de más de N días. */
    public function limpiarViejas(int $dias = 365): void
    {
        $stmt = $this->db->prepare("DELETE FROM visitas WHERE fecha < NOW() - INTERVAL ? DAY");
        $stmt->bind_param("i", $dias);
        $stmt->execute();
    }
}