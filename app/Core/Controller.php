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
}