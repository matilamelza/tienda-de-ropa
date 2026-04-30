<?php

class DashboardController extends Controller
{
    public function index()
    {
        $pedidoModel = new Pedido();
        $productoModel = new Producto();

        $resumen = $pedidoModel->resumenDashboard();
        $ultimosPedidos = $pedidoModel->ultimosPedidos(6);
        $stockBajo = $productoModel->productosStockBajo(3);

        $this->view('dashboard/index', [
            'resumen' => $resumen,
            'ultimosPedidos' => $ultimosPedidos,
            'stockBajo' => $stockBajo
        ]);
    }
}