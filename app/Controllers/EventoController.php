<?php

class EventoController extends Controller
{
    /** POST desde la ficha del producto: el cliente tocó un talle. */
    public function talle()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idProducto = (int) ($_POST['id_producto'] ?? 0);
            $talle      = trim((string) ($_POST['talle'] ?? ''));

            // No repetir el mismo talle del mismo producto en 10 minutos
            $clave = $idProducto . '|' . $talle;
            $ultimo = $_SESSION['ultimo_talle'][$clave] ?? 0;

            if ($idProducto > 0 && $talle !== '' && time() - $ultimo > 600) {
                $_SESSION['ultimo_talle'][$clave] = time();

                registrar_evento('talle', [
                    'id_producto' => $idProducto,
                    'talle'       => $talle,
                    'con_stock'   => !empty($_POST['con_stock']),
                ]);
            }
        }

        http_response_code(204);   // sin contenido: el navegador no espera nada
        exit;
    }
}