<?php

namespace Service\action\affichage;

use Service\action\Action;
use Service\repository\DeefyRepository;

class FiltreCatalogueAction extends Action
{
    public function getResult(): string
    {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        $pdo = DeefyRepository::getInstance()->getPDO();

        // Récupération des filtres dans l’URL
        $genreChoisi = $_GET['genre'] ?? '';
        $publicChoisi = $_GET['public'] ?? '';

        // Requête principale
        $sql = "
            SELECT DISTINCT s.id_serie, s.titre_serie, s.img
            FROM serie s
            LEFT JOIN genre2serie gs ON s.id_serie = gs.id_serie
            LEFT JOIN genre g ON gs.id_genre = g.id_genre
            LEFT JOIN public2serie ps ON s.id_serie = ps.id_serie
            LEFT JOIN public_cible pc ON ps.id_public = pc.id_public
            WHERE 1=1
        ";

        $params = [];

        // Ajout des filtres dynamiques
        if (!empty($genreChoisi)) {
            $sql .= " AND g.libelle = :genre";
            $params[':genre'] = $genreChoisi;
        }

        if (!empty($publicChoisi)) {
            $sql .= " AND pc.libelle = :public";
            $params[':public'] = $publicChoisi;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $series = $stmt->fetchAll();

        // Récupération des genres et publics pour le formulaire
        $genres = $pdo->query("SELECT DISTINCT libelle FROM genre ORDER BY libelle")->fetchAll();
        $publics = $pdo->query("SELECT DISTINCT libelle FROM public_cible ORDER BY libelle")->fetchAll();

        // HTML du formulaire de filtrage
        $html = "
        <h2>Filtrer le catalogue</h2>
        <form method='get' action='' class='catalogue-form'>
            <input type='hidden' name='action' value='CatalogueFiltre'>

            <label for='genre'>Genre :</label>
            <select name='genre' id='genre'>
                <option value=''>-- Tous les genres --</option>";

        foreach ($genres as $g) {
            $lib = htmlspecialchars($g['libelle']);
            $selected = ($lib === $genreChoisi) ? 'selected' : '';
            $html .= "<option value='{$lib}' {$selected}>{$lib}</option>";
        }

        $html .= "</select>

            <label for='public'>Public :</label>
            <select name='public' id='public'>
                <option value=''>-- Tous les publics --</option>";

        foreach ($publics as $p) {
            $lib = htmlspecialchars($p['libelle']);
            $selected = ($lib === $publicChoisi) ? 'selected' : '';
            $html .= "<option value='{$lib}' {$selected}>{$lib}</option>";
        }

        $html .= "</select>
            <button type='submit'>Filtrer</button>
        </form>
        ";

        // Affichage des séries filtrées
        if (empty($series)) {
            $html .= "<p>Aucune série trouvée pour ce filtre.</p>";
        } else {
            $html .= "<div class='series-grid'>";
            foreach ($series as $s) {
                $id = (int)$s['id_serie'];
                $titre = htmlspecialchars($s['titre_serie']);
                $image = htmlspecialchars($s['img'] ?? 'a.png');
                $html .= "
                    <div class='serie-card'>
                        <a href='?action=AfficherSerie&id={$id}'>
                            <img src='../../../img/{$image}' alt='Image de la série {$titre}' class='serie-img'>
                        </a>
                        <a href='?action=AfficherSerie&id={$id}' class='serie-title'>{$titre}</a>
                    </div>
                ";
            }
            $html .= "</div>";
        }

        return $html;
    }
}
