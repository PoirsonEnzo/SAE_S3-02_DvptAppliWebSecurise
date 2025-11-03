<?php
namespace Service\action;

use Service\auth\AuthnProvider;

class SigninAction extends Action
{
    public function getResult(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return <<<HTML
            <div class="max-w-md mx-auto p-6 bg-white rounded shadow-md">
                <h2 class="text-2xl font-bold mb-4">Connexion</h2>
                <form method="post" action="?action=SignIn" class="space-y-4">
                    <div>
                        <label for="email" class="block font-semibold">Email :</label>
                        <input type="email" id="email" name="email" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label for="password" class="block font-semibold">Mot de passe :</label>
                        <input type="password" id="password" name="password" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                        Se connecter
                    </button>
                </form>
            </div>
HTML;
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        try {
            $user = AuthnProvider::signin($email, $password);
            $_SESSION['user'] = $user;

            return <<<HTML
            <p class="text-green-500 font-semibold">Connexion réussie ! Bienvenue, <strong>{$user['email']}</strong>.</p>
            <p><a href="?action=home" class="text-blue-500 hover:underline">Retour à l’accueil</a></p>
HTML;

        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            return "<p class='text-red-500 font-semibold'>Erreur interne : {$msg}</p>";
        }
    }
}
