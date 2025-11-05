<?php

namespace Service\dispatch;

use Service\action\ActivateAccountAction;
use Service\action\AddUserAction;
use Service\action\AfficherCatalogue;
use Service\action\AfficherEpisode;
use Service\action\AfficherSerie;
use Service\action\RechercheMotCleAction;
use Service\action\SigninAction;
use Service\action\SignoutAction;
use Service\action\DefaultAction;
use Service\auth\AuthnProvider;
use Service\action\ajouterFavorisAction;
use Service\action\addProfilAction;

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
            case 'Catalogue':
                $act = new AfficherCatalogue();
                break;
            case 'afficherSerie':
                $act = new AfficherSerie();
                break;
            case 'afficherEpisode':
                $act = new AfficherEpisode();
                break;
            case 'ajouterFavorisAction':
                $act = new ajouterFavorisAction();
                break;
            case 'addProfilAction':
                $act = new addProfilAction();
                break;
            case 'activateAccount':
                $act = new ActivateAccountAction();
                break;
            case 'ChoisirProfilAction':
                $act = new \Service\action\ChoisirProfilAction();
                break;
            case 'ProfiActiflAction':
                $act = new \Service\action\ProfiActiflAction();
                break;
            case 'RechercheMotCle':
                $act = new RechercheMotCleAction();
                break;
            default:
                $act = new DefaultAction();
                break;
        }

        $this->renderPage($act->getResult());
    }

    private function renderPage(string $html): void {
        if (!AuthnProvider::isUserRegistered()) {
            $lien_auth = '<a href="?action=AddUser">Inscription</a> |<a href="?action=SignIn">Connexion</a>';
        } else {
            $lien_auth = '<a href="?action=SignOut">Déconnexion</a>';
        } 

        echo <<<PAGE
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>NetVOD</title>
            <link rel="stylesheet" href="../css/style.css">
        </head>
        <body>
            <h1>NetVOD</h1>
            <nav>
                <a href="?action=default">Accueil</a> |
                 $lien_auth |
                <a href="?action=addProfilAction">Profil</a> |
                <a href="?action=ChoisirProfilAction">selection</a> |
                <a href="?action=Catalogue">Afficher le catalogue</a> |
               
                <a href="/SQL/db_init.php">Initialiser la BD</a>
            </nav>
            <hr>
            $html
        </body>
        </html>
        PAGE;
    }
}
