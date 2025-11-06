<?php

namespace Service\dispatch;

use Service\action\affichage\AfficherCatalogue;
use Service\action\affichage\AfficherEpisode;
use Service\action\affichage\AfficherSerie;
use Service\action\affichage\CatalogueTriAction;
use Service\action\affichage\FiltreCatalogueAction;
use Service\action\affichage\RechercheMotCleAction;
use Service\action\compte\ActivateAccountAction;
use Service\action\compte\AddUserAction;
use Service\action\compte\ForgotPasswordAction;
use Service\action\compte\ResetPasswordAction;
use Service\action\compte\SigninAction;
use Service\action\compte\SignoutAction;
use Service\action\DefaultAction;
use Service\action\InitDB;
use Service\action\interaction\AjouterFavorisAction;
use Service\action\interaction\CommentaireAction;
use Service\action\interaction\SuppCommentaireAction;
use Service\action\interaction\SupprimerFavorisAction;
use Service\action\profil\AddProfilAction;
use Service\action\profil\ChoisirProfilAction;
use Service\action\profil\ProfilActifAction;
use Service\action\profil\QuitterProfilAction;
use Service\auth\AuthnProvider;

class Dispatcher {

    private string $action;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->action = $_GET['action'] ?? 'default';
    }

    public function run(): void {
        switch ($this->action) {
            case 'AddUser':             $act = new AddUserAction(); break;
            case 'SignIn':              $act = new SigninAction(); break;
            case 'SignOut':             $act = new SignoutAction(); break;
            case 'Catalogue':           $act = new AfficherCatalogue(); break;
            case 'AfficherSerie':       $act = new AfficherSerie(); break;
            case 'AfficherEpisode':     $act = new AfficherEpisode(); break;
            case 'AjouterFavoris':      $act = new AjouterFavorisAction(); break;
            case 'SupFavoris':          $act = new SupprimerFavorisAction(); break;
            case 'AddProfilAction':     $act = new AddProfilAction(); break;
            case 'activateAccount':     $act = new ActivateAccountAction(); break;
            case 'ChoisirProfilAction': $act = new ChoisirProfilAction(); break;
            case 'ProfilActifAction':   $act = new ProfilActifAction(); break;
            case 'RechercheMotCle':     $act = new RechercheMotCleAction(); break;
            case 'CatalogueTri':        $act = new CatalogueTriAction(); break;
            case 'ForgotPassword':      $act = new ForgotPasswordAction(); break;
            case 'ResetPassword':       $act = new ResetPasswordAction(); break;
            case 'Commentaire':         $act = new CommentaireAction(); break;
            case 'supprimerCom':        $act = new SuppCommentaireAction(); break;
            case 'SignoutProfilAction': $act = new QuitterProfilAction(); break;
            case 'CatalogueFiltre':     $act = new FiltreCatalogueAction(); break;
            case 'InitDB':              $act = new InitDB(); break;
            default:                    $act = new DefaultAction(); break;
        }

        $this->renderPage($act->getResult());
    }

    private function renderPage(string $html): void {

        // --- Lien connexion / déconnexion ---
        if (!AuthnProvider::isUserRegistered()) {
            $lien_auth = '<a href="?action=AddUser">Inscription</a> | <a href="?action=SignIn">Connexion</a>';
        } else {
            $lien_auth = '<a href="?action=SignOut">Déconnexion</a>';
        }

        // --- Affichage du profil actif uniquement si sélectionné ---
        if (!empty($_SESSION['profil']) && !empty($_SESSION['profil']['username'])) {
            $username = htmlspecialchars($_SESSION['profil']['username']);
            $imgProfil = htmlspecialchars($_SESSION['profil']['img_profil'] ?? 'img/Profil/DefaultProfil.png');

            $compte_actif = <<<HTML
            <div class='compte-actif' style="display:flex; align-items:center; gap:10px;">
                Profil actif : <strong>{$username}</strong>
                <img src="{$imgProfil}" alt="Avatar" style="width:50px; height:50px; border-radius:50%;">
            </div>
            HTML;
        } else {
            $compte_actif = "<div class='compte-actif'>Aucun profil actif</div>";
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'webetu.iutnc.univ-lorraine.fr') !== false) {
            // En ligne (serveur Webetu)
            $cssPath = "css/style.css";
        } else {
            // En local (Docker)
            $cssPath = "../css/style.css";
        }

        echo <<<PAGE
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>NetVOD</title>
            <link rel="stylesheet" href=$cssPath>
        </head>
        <body>
            <h1>NetVOD</h1>
            $compte_actif

            <nav>
                <a href="?action=default">Accueil</a> |
                $lien_auth |
                <a href="?action=AddProfilAction">Profil</a> |
                <a href="?action=ChoisirProfilAction">Sélection</a> |
                <a href="?action=Catalogue">Catalogue</a> |
                <a href="?action=InitDB">Initialiser la BD</a>
            </nav>
            <hr>
            $html
        </body>
        </html>
        PAGE;
    }
}
