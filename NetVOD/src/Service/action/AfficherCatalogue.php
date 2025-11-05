<?php

namespace Service\action;

use Service\repository\DeefyRepository;

class AfficherCatalogue extends Action
{
    public function getResult(): string
    {
        // Vérification si un utilisateur est connecté
        if (isset($_SESSION['user'])) {

            $html = "
                <h2>Catalogue des séries</h2>
                <div class='catalogue-top'>
                    <form method='get' action='' class='search-bar'>
                        <input type='hidden' name='action' value='CatalogueTri'>
                        <input type='text' name='search' placeholder='Rechercher une série...'>
                        <select name='tri'>
                            <option value='titre_serie'>Titre</option>
                            <option value='date_ajout'>Date d’ajout</option>
                            <option value='nb_episodes'>Nombre d’épisodes</option>
                        </select>
                        <select name='ordre'>
                            <option value='ASC'>Croissant</option>
                            <option value='DESC'>Décroissant</option>
                        </select>
                        <button type='submit'>🔍</button>
                    </form>
                </div>
            ";

            $pdo = DeefyRepository::getInstance()->getPDO();

            // 🔹 On récupère aussi le champ 'img'
            $stmt = $pdo->prepare("SELECT id_serie, titre_serie, img FROM serie");
            $stmt->execute();
            $results = $stmt->fetchAll();

            // 🔹 Affichage du catalogue
            $html .= "<div class='series-grid'>";
            foreach ($results as $data) {
                $id = (int)$data['id_serie'];
                $titre = htmlspecialchars($data['titre_serie']);
                $image = htmlspecialchars($data['img'] ?? 'default.png'); // image par défaut si null

                // 🔹 On suppose que les images sont dans /img/
                $html .= "
                    <div class='serie-card'>
                        <img src='../../../img/{$image}' alt='Image de la série {$titre}' class='serie-img'>
                        <a href='?action=afficherSerie&id={$id}'>{$titre}</a>
                    </div>
                ";
            }
            $html .= "</div>";

            return $html;

        } else {
            // Utilisateur non connecté
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }
    }
}
