<?php

namespace Service\action;

use Service\repository\DeefyRepository;
use \PDO;

class SuppCommentaireAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }
        if (!isset($_GET['id'])) {
            return "<p>Aucun commentaire sélectionné.</p>";
        }
        $idCom = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();


        $stmt = $pdo->prepare("
        DELETE FROM commentaire
        WHERE id_commentaire = ?
        ");
        $stmt->execute([$idCom]);

        return "<p>Commentaire supprimé</p>
                <p><a href='?action=default'>Retourner à l'accueil</a> </p>";
    }
}