<?php


namespace Service\action;

use Service\repository\DeefyRepository;
use \PDO;

class CommentaireAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        if (!isset($_GET['id'])) {
            return "<p>Aucune série sélectionnée.</p>";
        }

        $idSerie = (int)$_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();
        return '';
    }
}
