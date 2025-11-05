<?php
namespace Service\action;

class QuitterProfilAction extends Action
{
    public function getResult(): string
    {
        if (isset($_SESSION['profil'])) {
            unset($_SESSION['profil']);
        }

        return "<p>Profil déconnecté avec succès.</p>
                <p><a href='?action=ChoisirProfilAction'>Choisir un autre profil</a></p>";
    }
}
