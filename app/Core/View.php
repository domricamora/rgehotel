<?php
namespace App\Core;

/**
 * Simple PHP template renderer with layout support.
 */
class View
{
    private static string $viewPath = '';

    private static function path(): string
    {
        if (self::$viewPath === '') {
            self::$viewPath = dirname(__DIR__) . '/Views';
        }
        return self::$viewPath;
    }

    /**
     * Render a view into a layout.
     * @param string $view  dot path e.g. "public.home"
     * @param array  $data  variables exposed to the view
     * @param string|null $layout dot path under layouts/, or null for none
     */
    public static function render(string $view, array $data = [], ?string $layout = 'main'): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        $layoutFile = self::path() . '/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            return $content;
        }
        $data['content'] = $content;
        extract($data, EXTR_SKIP);
        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    /** Render a view fragment with no layout. */
    public static function partial(string $view, array $data = []): string
    {
        $file = self::path() . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            return "<!-- view not found: {$view} -->";
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean();
    }
}
