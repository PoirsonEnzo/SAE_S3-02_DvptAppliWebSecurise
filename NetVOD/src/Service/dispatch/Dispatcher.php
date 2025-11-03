<?php

namespace Service\dispatch;

use Service\action\DefaultAction;

class Dispatcher {

    private string $action;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->action = $_GET['Service.action'] ?? 'default';
    }

    public function run(): void {
        switch ($this->action) {
            default:
                $act = new DefaultAction();
                break;
        }

        $this->renderPage($act->getResult());
    }

    private function renderPage(string $html): void {
        echo <<<PAGE
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>NetVOD</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 2em; background: #fafafa; }
                nav a { margin-right: 1em; text-decoration: none; color: #0077cc; }
                nav a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <h1>NetVOD</h1>
            <nav>
                <a href="?Service.action=default">Accueil</a> |


            </nav>
            <hr>
            $html
        </body>
        </html>
        PAGE;
    }
}
