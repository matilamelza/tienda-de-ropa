<?php

require_once __DIR__ . '/../Models/ConfiguracionTienda.php';

class ConfiguracionController extends Controller
{
    private ConfiguracionTienda $model;

    public function __construct()
    {
        $this->model = new ConfiguracionTienda();
    }

    // GET /admin/configuracion
    public function index(): void
    {
        $grupos  = $this->model->porGrupo();
        $config  = $this->model->todas();
        $mensaje = $_SESSION['config_msg'] ?? null;
        unset($_SESSION['config_msg']);

        $this->view('admin/configuracion/index', [
            'grupos'  => $grupos,
            'config'  => $config,
            'mensaje' => $mensaje,
        ]);
    }

    // POST /admin/configuracion/guardar
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/configuracion');
            return;
        }

        $datos = [];

        $camposTexto = [
            'tienda_nombre', 'tienda_slogan', 'tienda_descripcion',
            'color_primario', 'color_secundario', 'color_acento',
            'color_fondo', 'color_texto', 'color_header_bg',
            'color_footer_bg', 'color_boton_bg', 'color_boton_texto',
            'fuente_principal', 'fuente_titulos', 'tamano_base',
            'tienda_email', 'tienda_telefono', 'tienda_whatsapp',
            'tienda_direccion', 'tienda_instagram', 'tienda_facebook', 'tienda_tiktok',
            'hero_titulo', 'hero_subtitulo', 'hero_boton_texto', 'hero_estilo',
            'seo_titulo', 'seo_descripcion', 'seo_keywords',
            'envio_gratis_minimo', 'politica_cambios', 'metodos_pago',
            'anuncio_texto', 'anuncio_color_bg', 'anuncio_color_texto',
            'footer_texto',
            'productos_por_pagina', 'moneda_simbolo', 'moneda_codigo',
            'mantenimiento_mensaje',
        ];

        foreach ($camposTexto as $campo) {
            $datos[$campo] = trim($_POST[$campo] ?? '');
        }

        $checkboxes = [
            'anuncio_activo', 'footer_mostrar_redes',
            'mostrar_precio', 'mostrar_stock',
            'permitir_invitados', 'mantenimiento_activo',
        ];
        foreach ($checkboxes as $campo) {
            $datos[$campo] = isset($_POST[$campo]) ? '1' : '0';
        }

        $imagenes = ['tienda_logo' => 'logo', 'tienda_favicon' => 'favicon', 'hero_imagen' => 'hero'];
        foreach ($imagenes as $campo => $tipo) {
            if (!empty($_FILES[$campo]['name'])) {
                $ruta = $this->model->subirImagen($_FILES[$campo], $tipo);
                if ($ruta !== false) {
                    $datos[$campo] = $ruta;
                }
            }
        }

        $ok = $this->model->guardarMultiple($datos);

        $_SESSION['config_msg'] = $ok
            ? ['tipo' => 'ok',    'texto' => 'Configuración guardada correctamente.']
            : ['tipo' => 'error', 'texto' => 'Ocurrió un error al guardar. Intentá de nuevo.'];

        $this->redirect(BASE_URL . '/admin/configuracion');
    }

    // POST /admin/configuracion/eliminar-imagen
    public function eliminarImagen(): void
    {
        $campo     = $_POST['campo'] ?? '';
        $permitidos = ['tienda_logo', 'tienda_favicon', 'hero_imagen'];

        if (in_array($campo, $permitidos, true)) {
            $rutaActual = $this->model->get($campo);
            if ($rutaActual) {
                $archivoFisico = __DIR__ . '/../../public/' . $rutaActual;
                if (file_exists($archivoFisico)) {
                    unlink($archivoFisico);
                }
            }
            $this->model->guardar($campo, '');
        }

        $this->redirect(BASE_URL . '/admin/configuracion');
    }
}