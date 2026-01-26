<?php

namespace Src\Core;

use Src\Core\TwigRenderer;

abstract class BaseController
{
    protected TwigRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new TwigRenderer();
    }

    public function render(string $view, ?array $data = [])
    {
        $this->renderer->render($view, $data);
    }

    public function redirect(string $path): void
    {
        header("Location: $path");
        exit;
    }
}