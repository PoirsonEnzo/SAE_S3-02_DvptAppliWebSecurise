<?php
namespace Service\action\compte;

use Service\action\Action;

class SignoutAction extends Action
{
    public function getResult(): string
    {
        session_unset();
        session_destroy();

        return <<<HTML
        <div class="center-message">
            <h2>Vous avez été déconnecté</h2>
            <p>À bientôt ! Connectez-vous à nouveau pour continuer.</p>
            <div class="btn-container">
                <a href="?action=SignIn" class="btn-center">Se reconnecter</a>
                <a href="?action=AddUser" class="btn-center">S’inscrire</a>
            </div>
        </div>
HTML;
    }
}
