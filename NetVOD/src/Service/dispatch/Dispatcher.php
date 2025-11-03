<?php

namespace Service\dispatch;

use Service\action\AddUserAction;
use Service\action\SigninAction;
use Service\action\SignoutAction;
use Service\action\DefaultAction;

class Dispatcher {

    private string $action;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->action = $_GET['action'] ?? 'default';
    }

    public function run(): void {
        switch ($this->action) {
            case 'AddUser':
                $act = new AddUserAction();
                break;
            case 'SignIn':
                $act = new SigninAction();
                break;
            case 'SignOut':
                $act = new SignoutAction();
                break;
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
                <a href="?action=default">Accueil</a> |
                <a href="?action=AddUser">Inscription</a> |
                <a href="?action=SignIn">Connexion</a> |
                <a href="?action=SignOut">Déconnexion</a> |
            </nav>
            <hr>
            $html
        </body>
        </html>
        PAGE;
    }
}
