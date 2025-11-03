<?php
namespace iutnc\deefy\action;

class SignoutAction extends Action
{
    public function execute(): string
    {
        session_unset();
        session_destroy();
        return <<<HTML
        <p class="text-green-500 font-semibold">Vous avez été déconnecté.</p>
        <p><a href="?action=signin" class="text-blue-500 hover:underline">Se reconnecter</a></p>
HTML;
    }
}
