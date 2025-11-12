<?php

namespace Service\action\compte;

use Service\action\Action;
use Service\auth\AuthnProvider;

class ForgotPasswordAction extends Action
{
    public function getResult(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return <<<HTML
            <div class="center-message">
                <h2>Mot de passe oublié</h2>
                <form method="POST" action="?action=ForgotPassword" style="display:flex; flex-direction:column; align-items:center; gap:12px; width:320px;">
                    <label for="email" style="font-weight:bold;">Email :</label>
                    <input type="email" id="email" name="email" required class="catalogue-form-input">
                    <button type="submit" class="btn-center">Générer le lien</button>
                </form>
            </div>
HTML;
        }

        // POST
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        try {
            $link = (new AuthnProvider)->generateResetToken($email);

            return <<<HTML
            <div class="center-message">
                <p style="color:green; font-weight:bold;">Lien de réinitialisation généré avec succès !</p>
                <div class="btn-container">
                    <a href="{$link}" class="btn-center">Réinitialiser le mot de passe</a>
                </div>
            </div>
HTML;

        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            return <<<HTML
            <div class="center-message">
                <p style="color:red; font-weight:bold;">Erreur : {$msg}</p>
                <div class="btn-container">
                    <a href="?action=ForgotPassword" class="btn-center">Réessayer</a>
                </div>
            </div>
HTML;
        }
    }
}
