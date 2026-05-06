<?php

/**
 * Model: ConfiguracionTienda
 * Gestiona todas las configuraciones de la tienda desde la BD.
 */
class ConfiguracionTienda
{
    private $db;
    private static array $cache = [];   // evita múltiples queries en el mismo request

    public function __construct()
    {
        $db = require __DIR__ . '/../../config/database.php';
        $this->db = $db['conexion'];
    }

    // ─────────────────────────────────────────────────────────
    // LECTURA
    // ─────────────────────────────────────────────────────────

    /** Devuelve el valor de una clave, o $defecto si no existe. */
    public function get(string $clave, string $defecto = ''): string
    {
        if (!isset(self::$cache[$clave])) {
            self::$cache = $this->_cargarTodas();
        }
        return self::$cache[$clave] ?? $defecto;
    }

    /** Devuelve TODAS las configuraciones como array asociativo clave=>valor. */
    public function todas(): array
    {
        if (empty(self::$cache)) {
            self::$cache = $this->_cargarTodas();
        }
        return self::$cache;
    }

    /** Devuelve las configuraciones agrupadas por grupo. */
    public function porGrupo(): array
    {
        $sql  = "SELECT `clave`, `valor`, `grupo` FROM `configuracion_tienda` ORDER BY `grupo`, `clave`";
        $res  = $this->db->query($sql);
        $grupos = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $grupos[$row['grupo']][$row['clave']] = $row['valor'];
            }
        }

        return $grupos;
    }

    // ─────────────────────────────────────────────────────────
    // ESCRITURA
    // ─────────────────────────────────────────────────────────

    /**
     * Guarda un array de claves => valores.
     * Usa INSERT … ON DUPLICATE KEY UPDATE para crear o actualizar.
     */
    public function guardarMultiple(array $datos): bool
    {
        if (empty($datos)) return true;

        $stmt = $this->db->prepare(
            "INSERT INTO `configuracion_tienda` (`clave`, `valor`)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`)"
        );

        if (!$stmt) return false;

        $this->db->begin_transaction();
        try {
            foreach ($datos as $clave => $valor) {
                $clave = substr(trim($clave), 0, 100);
                $stmt->bind_param('ss', $clave, $valor);
                $stmt->execute();
            }
            $this->db->commit();
            self::$cache = []; // invalidar cache
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /** Guarda una sola clave. */
    public function guardar(string $clave, string $valor): bool
    {
        return $this->guardarMultiple([$clave => $valor]);
    }

    // ─────────────────────────────────────────────────────────
    // LOGO / IMÁGENES
    // ─────────────────────────────────────────────────────────

    /**
     * Sube una imagen (logo, favicon, hero) y devuelve la ruta relativa,
     * o false si falla.
     *
     * @param  array  $file  Elemento de $_FILES
     * @param  string $tipo  'logo' | 'favicon' | 'hero'
     * @return string|false
     */
    public function subirImagen(array $file, string $tipo = 'logo')
    {
        $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon'];

        if (!in_array($file['type'], $permitidos, true)) {
            return false;
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5 MB
            return false;
        }

        $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombre    = $tipo . '_' . time() . '.' . $ext;
        $directorio = __DIR__ . '/../../public/uploads/tienda/';

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $destino = $directorio . $nombre;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            return false;
        }

        return 'uploads/tienda/' . $nombre;
    }

    // ─────────────────────────────────────────────────────────
    // CSS DINÁMICO
    // ─────────────────────────────────────────────────────────

    /**
     * Genera el bloque <style> con las variables CSS personalizadas.
     * Incluir en el <head> de todos los layouts.
     */
    public function generarCSS(): string
    {
        $c = $this->todas();

        // Sanitizar valores de color (solo hex y rgb válidos)
        $sanitize = function(string $valor): string {
            $valor = trim($valor);
            if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $valor)) {
                return $valor;
            }
            if (preg_match('/^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $valor)) {
                return $valor;
            }
            return '#111827'; // fallback seguro
        };

        $primario      = $sanitize($c['color_primario']      ?? '#111827');
        $secundario    = $sanitize($c['color_secundario']    ?? '#6B7280');
        $acento        = $sanitize($c['color_acento']        ?? '#F59E0B');
        $fondo         = $sanitize($c['color_fondo']         ?? '#FFFFFF');
        $texto         = $sanitize($c['color_texto']         ?? '#111827');
        $headerBg      = $sanitize($c['color_header_bg']     ?? '#FFFFFF');
        $footerBg      = $sanitize($c['color_footer_bg']     ?? '#111827');
        $botonBg       = $sanitize($c['color_boton_bg']      ?? '#111827');
        $botonTexto    = $sanitize($c['color_boton_texto']   ?? '#FFFFFF');

        $fuente        = htmlspecialchars($c['fuente_principal'] ?? 'Inter', ENT_QUOTES);
        $fuenteTitulos = htmlspecialchars($c['fuente_titulos']   ?? 'Inter', ENT_QUOTES);
        $tamano        = (int)($c['tamano_base'] ?? 16);

        return <<<CSS
<style>
  :root {
    --color-primario:    {$primario};
    --color-secundario:  {$secundario};
    --color-acento:      {$acento};
    --color-fondo:       {$fondo};
    --color-texto:       {$texto};
    --color-header-bg:   {$headerBg};
    --color-footer-bg:   {$footerBg};
    --color-boton-bg:    {$botonBg};
    --color-boton-texto: {$botonTexto};
    --fuente-principal:  '{$fuente}', sans-serif;
    --fuente-titulos:    '{$fuenteTitulos}', sans-serif;
    --tamano-base:       {$tamano}px;
  }
  body {
    font-family: var(--fuente-principal);
    font-size:   var(--tamano-base);
    background:  var(--color-fondo);
    color:       var(--color-texto);
  }
  h1, h2, h3, h4, h5, h6 {
    font-family: var(--fuente-titulos);
  }
  .btn-primario {
    background-color: var(--color-boton-bg);
    color:            var(--color-boton-texto);
  }
  .btn-primario:hover {
    opacity: 0.85;
  }
  header {
    background-color: var(--color-header-bg);
  }
  footer {
    background-color: var(--color-footer-bg);
  }
</style>
CSS;
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────

    private function _cargarTodas(): array
    {
        $sql = "SELECT `clave`, `valor` FROM `configuracion_tienda`";
        $res = $this->db->query($sql);
        $datos = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $datos[$row['clave']] = $row['valor'];
            }
        }

        return $datos;
    }
}