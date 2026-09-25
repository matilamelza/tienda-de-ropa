<?php

class Controller
{
    protected function view($view, $data = [], $layout = 'admin')
    {
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            die('La vista no existe: ' . $view);
        }

        require __DIR__ . '/../Views/layouts/' . $layout . '.php';
    }

    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

        /** Responde JSON y corta la ejecución. */
    protected function json(array $data, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}