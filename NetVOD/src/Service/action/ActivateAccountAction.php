<?php
namespace Service\action;

use PDO;
use Service\repository\DeefyRepository;

class ActivateAccountAction extends Action
{
    public function getResult(): string
    {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            return "<p class='text-red-500'>Lien d’activation invalide.</p>";
        }

        $pdo = DeefyRepository::getInstance()->getPDO();

        $stmt = $pdo->prepare("
            SELECT id_utilisateur FROM activation_token
            WHERE token = :token AND expiration > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return "<p class='text-red-500'>Lien d’activation expiré ou invalide.</p>";
        }

        // Active le compte
        $pdo->prepare("UPDATE utilisateur SET actif = TRUE WHERE id_utilisateur = :id")
            ->execute(['id' => $row['id_utilisateur']]);

        // Supprime le token
        $pdo->prepare("DELETE FROM activation_token WHERE token = :token")
            ->execute(['token' => $token]);

        return "
            <p class='text-green-500 font-semibold'>✅ Votre compte a bien été activé !</p>
            <a href='?action=SignIn' class='text-blue-600 underline'>Se connecter</a>
        ";
    }
}
