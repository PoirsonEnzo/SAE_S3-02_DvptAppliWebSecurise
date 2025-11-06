<?php
namespace Service\action\profil;

use Service\action\Action;
use Service\repository\DeefyRepository;

class ProfilActifAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return "<p>Vous devez être connecté pour activer un profil.</p>";
        }

        if (!isset($_GET['id'])) {
            return "<p>Aucun profil sélectionné.</p>";
        }

        $idProfil = (int) $_GET['id'];
        $idUtilisateur = (int) $_SESSION['user']['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // Vérifier que ce profil appartient bien à l'utilisateur
        $stmt = $pdo->prepare("
            SELECT p.id_profil, p.username
            FROM profil p
            WHERE p.id_profil = ?
        ");
        $stmt->execute([$idProfil]);
        $profil = $stmt->fetch();

        if (!$profil) {
            return "<p>Profil invalide ou non autorisé.</p>";
        }

        // Mettre le profil dans la session
        $_SESSION['profil'] = [
            'id_profil' => $profil['id_profil'],
            'username' => $profil['username']
        ];

        // Redirection vers la page d'accueil
        header("Location: ?action=DefaultAction");
        exit();
    }
}
