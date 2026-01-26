<?php
namespace Src\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigRenderer
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../Views');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);

        $this->twig->addFunction(new \Twig\TwigFunction('asset', function ($path) {
            return '/' . ltrim($path, '/');
        }));

        $this->twig->addFunction(new \Twig\TwigFunction('old', function ($key, $default = null) {
            return Session::flash('old.' . $key) ?? $default;
        }));

        $this->twig->addFunction(new \Twig\TwigFunction('error', function ($key) {
            return Session::flash('errors.' . $key);
        }));

        $this->twig->addFunction(new \Twig\TwigFunction('errors', function () {
            return Session::flash('errors') ?? [];
        }));

        $this->twig->addFunction(new \Twig\TwigFunction('success', function () {
            return Session::flash('success');
        }));
    }

    public function render(string $template, array $data = []): void
    {
        $data['user'] = Session::get('user');
        $data['csrf_token'] = Security::generateCSRFToken();

        $templateName = $template;
        if (substr($templateName, -5) !== '.twig') {
            $templateName .= '.twig';
        }

        echo $this->twig->render($templateName, $data);
    }
}
