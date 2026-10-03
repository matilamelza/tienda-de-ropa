<?php

class GuiaTallesController extends Controller
{
    private const CARPETA = __DIR__ . '/../../public/uploads/guias/';

    public function index()
    {
        $this->view('admin/guias/index', [
            'guias' => (new GuiaTalles())->listar(),
        ]);
    }

    /** Nueva (vacía o desde plantilla) o editar existente. */
    public function editar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $guia = (new GuiaTalles())->buscar($id);
            if (!$guia) {
                $this->redirect(BASE_URL . '/admin/guias');
            }
        } else {
            $guia = GuiaTalles::desdePlantilla($_GET['plantilla'] ?? '') ?? [
                'id_guia'  => 0,
                'nombre'   => '',
                'columnas' => [['nombre' => 'Talle', 'tipo' => 'talle'], ['nombre' => 'Medida (cm)', 'tipo' => 'medida']],
                'filas'    => [['', ''], ['', ''], ['', '']],
                'consejos' => '',
                'imagen'   => null,
            ];
        }

        $this->view('admin/guias/form', ['guia' => $guia]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/guias');
        }

        $modelo = new GuiaTalles();
        $id     = (int) ($_POST['id_guia'] ?? 0);
        $actual = $id > 0 ? $modelo->buscar($id) : null;
        if ($id > 0 && !$actual) {
            $this->redirect(BASE_URL . '/admin/guias');
        }

        $nombre = mb_substr(trim($_POST['nombre'] ?? ''), 0, 80);
        if ($nombre === '') {
            $this->volver('error', 'Poné un nombre para la guía.', $id);
        }

        [$columnas, $filas] = GuiaTalles::limpiar(
            (array) json_decode($_POST['columnas_json'] ?? '[]', true),
            (array) json_decode($_POST['filas_json'] ?? '[]', true)
        );
        if (!$filas) {
            $this->volver('error', 'Cargá al menos una fila con un talle.', $id);
        }

        $consejos = trim($_POST['consejos'] ?? '');
        $consejos = $consejos !== '' ? mb_substr($consejos, 0, 2000) : null;

        // Imagen: se mantiene, se reemplaza o se quita
        $imagen = $actual['imagen'] ?? null;
        if (!empty($_POST['quitar_imagen']) && $imagen) {
            $this->borrarArchivo($imagen);
            $imagen = null;
        }
        if (!empty($_FILES['imagen']['tmp_name'])) {
            $nueva = $this->subirImagen($_FILES['imagen']);
            if ($nueva === null) {
                $this->volver('error', 'La imagen tiene que ser JPG, PNG o WebP, de hasta 3 MB.', $id);
            }
            if ($imagen) {
                $this->borrarArchivo($imagen);
            }
            $imagen = $nueva;
        }

        $id = $modelo->guardar($id, $nombre, $columnas, $filas, $consejos, $imagen);

        $_SESSION['guias_msg'] = ['ok', 'Guía guardada.'];
        $this->redirect(BASE_URL . '/admin/guias');
    }

    public function duplicar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nuevo = (new GuiaTalles())->duplicar((int) ($_POST['id'] ?? 0));
            if ($nuevo) {
                $_SESSION['guias_msg'] = ['ok', 'Guía duplicada. Ajustala y guardá.'];
                $this->redirect(BASE_URL . '/admin/guias/editar?id=' . $nuevo);
            }
        }
        $this->redirect(BASE_URL . '/admin/guias');
    }

    public function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imagen = (new GuiaTalles())->eliminar((int) ($_POST['id'] ?? 0));
            if ($imagen) {
                $this->borrarArchivo($imagen);
            }
            $_SESSION['guias_msg'] = ['ok', 'Guía eliminada. Los productos que la usaban quedaron sin guía.'];
        }
        $this->redirect(BASE_URL . '/admin/guias');
    }

    // ─── HELPERS ───────────────────────────────────────────────────────────────

    private function volver(string $tipo, string $texto, int $id): void
    {
        $_SESSION['guias_msg'] = [$tipo, $texto];
        $this->redirect(BASE_URL . '/admin/guias/editar' . ($id ? '?id=' . $id : ''));
    }

    /** Valida que sea una imagen de verdad y la guarda con nombre aleatorio. */
    private function subirImagen(array $archivo): ?string
    {
        if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 3 * 1024 * 1024) {
            return null;
        }
        $info = @getimagesize($archivo['tmp_name']);
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if (!$ext) {
            return null;
        }

        if (!is_dir(self::CARPETA)) {
            mkdir(self::CARPETA, 0755, true);
        }
        $nombre = 'guia_' . bin2hex(random_bytes(8)) . '.' . $ext;

        return move_uploaded_file($archivo['tmp_name'], self::CARPETA . $nombre) ? $nombre : null;
    }

    private function borrarArchivo(string $nombre): void
    {
        $ruta = self::CARPETA . basename($nombre);
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}