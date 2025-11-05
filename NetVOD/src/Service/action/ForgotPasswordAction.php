<?php

namespace Service\action;

use Service\auth\AuthnProvider;

class ForgotPasswordAction extends Action
{
    public function getResult(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return <<<HTML
            <div class="max-w-md mx-auto p-6 bg-white rounded shadow-md">
                <h2 class="text-2xl font-bold mb-4">Mot de passe oublié</h2>
                <form method="POST" action="?action=ForgotPassword" class="space-y-4">
                    <div>
                        <label for="email" class="block font-semibold">Email :</label>
                        <input type="email" id="email" name="email" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                        Générer le lien
                    </button>
                </form>
            </div>
HTML;
        }

        // POST
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        try {
            $link = AuthnProvider::generateResetToken($email);

            return <<<HTML
            <p class="text-green-600 font-semibold">Lien de réinitialisation généré !</p>
            <p><a href="{$link}" class="text-blue-500 hover:underline">Cliquez ici pour modifier votre mot de passe</a></p>
HTML;

        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            return "<p class='text-red-500 font-semibold'>Erreur : {$msg}</p>";
        }
    }
}
