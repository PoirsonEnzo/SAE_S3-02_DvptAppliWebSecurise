<?php
namespace Service\action;
use Service\auth\AuthnProvider;
use Service\Exception\AuthnException;

class AddUserAction extends Action
{
    public function getResult(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return <<<HTML
            <div class="max-w-md mx-auto p-6 bg-white rounded shadow-md">
                <h2 class="text-2xl font-bold mb-4">Créer un compte utilisateur</h2>
                <form method="post" action="?action=add-user" class="space-y-4">
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
                    <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                        Créer le compte
                    </button>
                </form>
            </div>
HTML;
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if ($password !== $password2) {
            return "<p class='text-red-500 font-semibold'>Les mots de passe ne correspondent pas.</p>";
        }

        try {
            $user = AuthnProvider::register($email, $password);
            return "<p class='text-green-500 font-semibold'>Compte créé avec succès pour <strong>{$user['email']}</strong></p>";
        } catch (AuthnException $e) {
            return "<p class='text-red-500 font-semibold'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
