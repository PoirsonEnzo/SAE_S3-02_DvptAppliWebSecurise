<?php

namespace Service\action\compte;

use Service\action\Action;
use Service\repository\DeefyRepository;

class ResetPasswordAction extends Action
{
    public function getResult(): string
    {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            return "<p>Token manquant.</p>";
        }

        $pdo = DeefyRepository::getInstance()->getPDO();

        // Vérifie le token
        $stmt = $pdo->prepare("
            SELECT id_utilisateur FROM utilisateur 
            WHERE token_activation = :token AND date_token > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return "<p>Token invalide ou expiré.</p>";
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Formulaire nouveau mot de passe
            return <<<HTML
            <div class="max-w-md mx-auto p-6 bg-white rounded shadow-md">
                <h2 class="text-2xl font-bold mb-4">Réinitialisation du mot de passe</h2>
                <form method="POST" action="?action=ResetPassword&token={$token}" class="space-y-4">
                    <div>
                        <label for="password" class="block font-semibold">Nouveau mot de passe :</label>
                        <input type="password" id="password" name="password" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                        Valider
                    </button>
                </form>
            </div>
HTML;
        }

        // POST : modification du mot de passe
        $newPassword = $_POST['password'] ?? '';
        if (strlen($newPassword) < 10) {
            return "<p class='text-red-500 font-semibold'>Le mot de passe doit contenir au moins 10 caractères.</p>";
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $update = $pdo->prepare("
            UPDATE utilisateur 
            SET mot_de_passe = :hash, token_activation = NULL, date_token = NULL
            WHERE id_utilisateur = :id
        ");
        $update->execute([
            'hash' => $hash,
            'id' => $user['id_utilisateur']
        ]);

        return "<p class='text-green-600 font-semibold'>Mot de passe modifié avec succès ! Vous pouvez maintenant <a href='?action=SignIn' class='text-blue-500 hover:underline'>vous connecter</a>.</p>";
    }
}
