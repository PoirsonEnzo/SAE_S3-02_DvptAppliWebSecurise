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
        $idUtilisateur = (int) $_SESSION['user']['id_utilisateur'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // On récupère aussi l'image du profil
        $stmt = $pdo->prepare("
            SELECT p.id_profil, p.username, p.img_profil
            FROM profil p
            WHERE p.id_profil = ? AND p.id_utilisateur = ?
        ");
        $stmt->execute([$idProfil, $idUtilisateur]);
        $profil = $stmt->fetch();

        if (!$profil) {
            return "<p>Profil invalide ou non autorisé.</p>";
        }

        // Enregistrement complet dans la session
        $_SESSION['profil'] = [
            'id_profil'  => $profil['id_profil'],
            'username'   => $profil['username'],
            'img_profil' => $profil['img_profil'] ?? 'DefaultProfil.png'
        ];

        // Redirection vers la page d'accueil
        header("Location: ?action=DefaultAction");
        exit();
    }
}
