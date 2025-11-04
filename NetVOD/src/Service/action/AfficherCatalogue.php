<?php

namespace Service\action;

use Service\repository\DeefyRepository;

class AfficherCatalogue extends Action
{
    public function getResult(): string
    {
        //Verification si un utilisateur est connecté
        if (isset($_SESSION['user'])) {

            $html = "<h2>Afficher le catalogue des series</h2>";

            //Verification si playlist courante
            if(isset($_SESSION['user'])) {

                $pdo = DeefyRepository::getInstance()->getPDO();

                //Recuperation des pistes de la playlist
                $stmt = $pdo->prepare("SELECT id_serie, titre_serie FROM serie");
                $stmt->execute();
                $results = $stmt->fetchAll();

                $html .= "<h3>Catalogue des séries</h3><div class='series-grid'>";
                foreach ($results as $data) {
                    $titre = htmlspecialchars($data['titre_serie']);
                    $id = (int)$data['id_serie'];
                    $html .= "
                        <div class='serie-card'>
                            <img src='../../../img/a.jpg' alt='Image de la série {$titre}' class='serie-img'>
                            <a href='?action=afficherSerie&id={$id}'>{$titre}</a>
                        </div>
                        <br>
                    ";
                }
                $html .= "</div>";

            }else{
                //Dans le cas où pas de playlist courante
                $html = "<p>Pas de catalogue, il faut se connecter</p>";
            }
            return $html;
        }else{
            //Dans le cas ou l'utilisateur n'est pas connecté
            return '<br><h2>Il faut se connecter.</h2><p><a href="?action=SignIn">Se connecter</a> ou <a href="?action=AddUser">S’inscrire</a></p>';
        }
    }

}
