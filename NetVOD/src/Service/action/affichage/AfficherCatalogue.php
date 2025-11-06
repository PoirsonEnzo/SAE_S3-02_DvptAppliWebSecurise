<?php

namespace Service\action\affichage;

use Service\action\Action;
use Service\repository\DeefyRepository;

class AfficherCatalogue extends Action
{
    public function getResult(): string
    {
        // Vérification si un utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        $pdo = DeefyRepository::getInstance()->getPDO();
        $stmt = $pdo->prepare("SELECT id_serie, titre_serie, img FROM serie");
        $stmt->execute();
        $results = $stmt->fetchAll();

        // --- FORMULAIRE DE TRI + FILTRE ---
        $html = "
        <h2>Catalogue des séries</h2>
        <div class='catalogue-controls'>
            <form method='get' action='' class='catalogue-form'>
                <input type='hidden' name='action' value='CatalogueTri'>
                <div class='search-group'>
                    <input type='text' name='search' placeholder='🔍 Rechercher...' class='input-search'>
                    <select name='tri' class='select-tri'>
                        <option value='titre_serie'>Titre</option>
                        <option value='date_ajout'>Date d’ajout</option>
                        <option value='nb_episodes'>Nombre d’épisodes</option>
                        <option value='moy'>Note moyenne</option>
                    </select>
                    <select name='ordre' class='select-ordre'>
                        <option value='ASC'>Croissant</option>
                        <option value='DESC'>Décroissant</option>
                    </select>
                    <button type='submit' class='btn-apply'>Appliquer</button>
                    <a href='?action=CatalogueFiltre' class='btn-filter'> Filtrer par genre/public</a>
                </div>
            </form>
        </div>
        ";

        // --- Calcul dynamique du chemin des images ---
        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // chemin relatif à la racine Web
        $imgPrefix = $baseUrl . '/img/'; // fonctionnera sur Webetu et en local

        // --- AFFICHAGE DES SERIES ---
        $html .= "<div class='series-grid'>";
        foreach ($results as $data) {
            $id = (int)$data['id_serie'];
            $titre = htmlspecialchars($data['titre_serie']);
            $image = htmlspecialchars($data['img'] ?? 'a.png');

            $html .= "
                <div class='serie-card'>
                    <a href='?action=AfficherSerie&id={$id}'>
                        <img src='{$imgPrefix}{$image}' alt='Image de la série {$titre}' class='serie-img'>
                    </a>
                    <a href='?action=AfficherSerie&id={$id}' class='serie-title'>{$titre}</a>
                </div>
            ";
        }
        $html .= "</div>";

        return $html;
    }
}
