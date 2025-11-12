<?php
namespace Service\action\compte;

use PDO;
use Service\action\Action;
use Service\repository\DeefyRepository;

class ActivateAccountAction extends Action
{
    public function getResult(): string
    {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            return <<<HTML
            <div class="center-message">
                <h2>Lien d’activation invalide</h2>
                <p>Veuillez vérifier votre lien ou demander un nouveau lien d’activation.</p>
                <a href="?action=ForgotPassword" class="btn-center">Recevoir un nouveau lien</a>
            </div>
        HTML;
        }

        $pdo = DeefyRepository::getInstance()->getPDO();

        $stmt = $pdo->prepare("
            SELECT id_utilisateur FROM activation_token
            WHERE token = :token AND expiration > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return "<p class='text-red-500'>Lien d’activation expiré ou invalide. Essayez de vous reconnecter pour recevoir un nouveau lien.</p>";
        }

        // Active le compte
        $pdo->prepare("UPDATE utilisateur SET actif = 1 WHERE id_utilisateur = :id")
            ->execute(['id' => $row['id_utilisateur']]);

        // Supprime le token
        $pdo->prepare("DELETE FROM activation_token WHERE token = :token")
            ->execute(['token' => $token]);

        return <<<HTML
        <div class="center-message">
            <h2>Compte activé avec succès !</h2>
            <p>Votre compte a bien été activé.</p>
            <a href="?action=SignIn" class="btn-center">Se connecter</a>
        </div>
        HTML;
    }
}
