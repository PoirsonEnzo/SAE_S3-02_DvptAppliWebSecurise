<?php

namespace Service\action\affichage;

use Service\action\Action;
use Service\repository\DeefyRepository;

class AfficherCatalogue extends Action
{
    public function getResult(): string
    {
        // --- Vérification si l'utilisateur est connecté ---
        if (!isset($_SESSION['user'])) {
            return <<<HTML
    <div class="center-message">
        <h2>Il faut se connecter pour accéder au catalogue.</h2>
        <div class="btn-container">
            <a href="?action=SignIn" class="btn-center">Se connecter</a>
            <a href="?action=AddUser" class="btn-center">S’inscrire</a>
        </div>
    </div>
HTML;
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
        
                    <select name='genre' class='select-filter'>
                        <option value=''>-- Genre --</option>";

        $genres = $pdo->query("SELECT DISTINCT libelle FROM genre ORDER BY libelle")->fetchAll();
        foreach ($genres as $g) {
            $lib = htmlspecialchars($g['libelle']);
            $selected = (isset($_GET['genre']) && $_GET['genre'] === $lib) ? 'selected' : '';
            $html .= "<option value='{$lib}' {$selected}>{$lib}</option>";
        }

        $html .= "</select>
                    <select name='public' class='select-filter'>
                        <option value=''>-- Public --</option>";

        $publics = $pdo->query("SELECT DISTINCT libelle FROM public_cible ORDER BY libelle")->fetchAll();
        foreach ($publics as $p) {
            $lib = htmlspecialchars($p['libelle']);
            $selected = (isset($_GET['public']) && $_GET['public'] === $lib) ? 'selected' : '';
            $html .= "<option value='{$lib}' {$selected}>{$lib}</option>";
        }

        $html .= "</select>
                    <button type='submit' class='btn-apply'>Appliquer</button>
                </div>
            </form>
        </div>
        ";

        // --- Calcul dynamique du chemin des images ---
        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // chemin relatif à la racine Web
        $imgPrefix = $baseUrl . '/img/';

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
