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
        <p class="text-green-500 font-semibold">Vous avez été déconnecté.</p>
        <p><a href="?action=SignIn" class="text-blue-500 hover:underline">Se reconnecter</a></p>
HTML;
    }
}
