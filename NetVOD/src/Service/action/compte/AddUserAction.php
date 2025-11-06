<?php
namespace Service\action\compte;

use Service\action\Action;
use Service\auth\AuthnProvider;
use Service\Exception\AuthnException;
use Service\repository\DeefyRepository;

class AddUserAction extends Action
{
    public function getResult(): string
    {
        // Formulaire d'inscription
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return <<<HTML
            <div class="max-w-md mx-auto p-6 bg-white rounded shadow-md">
                <h2 class="text-2xl font-bold mb-4">Créer un compte utilisateur</h2>
                <form method="post" action="?action=AddUser" class="space-y-4">
                    <div>
                        <label for="email" class="block font-semibold">Email :</label>
                        <input type="email" id="email" name="email" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label for="password" class="block font-semibold">Mot de passe :</label>
                        <input type="password" id="password" name="password" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label for="password2" class="block font-semibold">Confirmer le mot de passe :</label>
                        <input type="password" id="password2" name="password2" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label for="carte" class="block font-semibold">Numéro de carte :</label>
                        <input type="text" id="carte" name="carte" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                        Créer le compte
                    </button>
                </form>
            </div>
HTML;
        }

        // Traitement du POST
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $carte = $_POST['carte'] ?? '';

        if ($password !== $password2) {
            return "<p class='text-red-500 font-semibold'>Les mots de passe ne correspondent pas.</p>";
        }

        try {
            $user = AuthnProvider::register($email, $password);
            $pdo = DeefyRepository::getInstance()->getPDO();

            // Récupération de l'ID utilisateur réel
            $stmtId = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
            $stmtId->execute([$email]);
            $idUtilisateur = (int)$stmtId->fetchColumn();

            // Mise à jour du numéro de carte
            $stmt = $pdo->prepare("UPDATE utilisateur SET num_carte = ? WHERE id_utilisateur = ?");
            $stmt->execute([$carte, $idUtilisateur]);

            // Création du token d'activation
            $token = bin2hex(random_bytes(32));
            $expiration = date('Y-m-d H:i:s', strtotime('+1 day'));
            $stmtToken = $pdo->prepare("INSERT INTO activation_token (id_utilisateur, token, expiration) VALUES (?, ?, ?)");
            $stmtToken->execute([$idUtilisateur, $token, $expiration]);

            // Bouton/lien pour activer le compte
            $activationLink = "?action=activateAccount&token=$token";

            return <<<HTML
            <p class='text-green-500 font-semibold'>Compte créé avec succès pour <strong>{$user['email']}</strong>.</p>
            <p>Pour activer votre compte, cliquez sur le bouton ci-dessous :</p>
            <a href="$activationLink" class="inline-block mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                Activer le compte
            </a>
HTML;

        } catch (AuthnException $e) {
            return "<p class='text-red-500 font-semibold'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
