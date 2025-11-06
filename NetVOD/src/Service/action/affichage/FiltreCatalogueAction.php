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

        // Récupération des paramètres
        $motCle = $_GET['search'] ?? '';
        $tri = $_GET['tri'] ?? 'titre_serie';
        $ordre = $_GET['ordre'] ?? 'ASC';
        $genreChoisi = $_GET['genre'] ?? '';
        $publicChoisi = $_GET['public'] ?? '';

        // Validation des valeurs autorisées
        $triValides = ['titre_serie', 'date_ajout', 'nb_episodes', 'moy'];
        $ordreValides = ['ASC', 'DESC'];
        if (!in_array($tri, $triValides)) $tri = 'titre_serie';
        if (!in_array($ordre, $ordreValides)) $ordre = 'ASC';

        // Requête SQL principale avec tri et filtres
        $sql = "
            SELECT s.id_serie, s.titre_serie, s.date_ajout, s.img,
                   COUNT(e.id_episode) AS nb_episodes,
                   AVG(c.note) AS moy
            FROM serie s
            LEFT JOIN episode e ON s.id_serie = e.id_serie
            LEFT JOIN commentaire_serie c ON s.id_serie = c.id_serie
            LEFT JOIN genre2serie gs ON s.id_serie = gs.id_serie
            LEFT JOIN genre g ON gs.id_genre = g.id_genre
            LEFT JOIN public2serie ps ON s.id_serie = ps.id_serie
            LEFT JOIN public_cible pc ON ps.id_public = pc.id_public
            WHERE 1=1
        ";

        $params = [];

        if (!empty($motCle)) {
            $sql .= " AND (s.titre_serie LIKE :motCle OR s.descriptif LIKE :motCle)";
            $params[':motCle'] = "%$motCle%";
        }
        if (!empty($genreChoisi)) {
            $sql .= " AND g.libelle = :genre";
            $params[':genre'] = $genreChoisi;
        }
        if (!empty($publicChoisi)) {
            $sql .= " AND pc.libelle = :public";
            $params[':public'] = $publicChoisi;
        }

        $sql .= " GROUP BY s.id_serie ORDER BY $tri $ordre";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Récupération des genres et publics pour les listes déroulantes
        $genres = $pdo->query("SELECT DISTINCT libelle FROM genre ORDER BY libelle")->fetchAll();
        $publics = $pdo->query("SELECT DISTINCT libelle FROM public_cible ORDER BY libelle")->fetchAll();

        // === FORMULAIRE DE TRI ET FILTRE ===
        $html = "
        <h2>Catalogue des séries</h2>
        <form method='get' action='' class='catalogue-form'>
            <input type='hidden' name='action' value='Catalogue'>

            <div class='search-group'>
                <input type='text' name='search' placeholder='🔍 Rechercher...' value='" . htmlspecialchars($motCle) . "' class='input-search'>

                <select name='tri' class='select-tri'>
                    <option value='titre_serie' " . ($tri === 'titre_serie' ? 'selected' : '') . ">Titre</option>
                    <option value='date_ajout' " . ($tri === 'date_ajout' ? 'selected' : '') . ">Date d’ajout</option>
                    <option value='nb_episodes' " . ($tri === 'nb_episodes' ? 'selected' : '') . ">Nombre d’épisodes</option>
                    <option value='moy' " . ($tri === 'moy' ? 'selected' : '') . ">Note moyenne</option>
                </select>

                <select name='ordre' class='select-ordre'>
                    <option value='ASC' " . ($ordre === 'ASC' ? 'selected' : '') . ">Croissant</option>
                    <option value='DESC' " . ($ordre === 'DESC' ? 'selected' : '') . ">Décroissant</option>
                </select>

                <select name='genre' class='select-genre'>
                    <option value=''>-- Tous les genres --</option>";
        foreach ($genres as $g) {
            $lib = htmlspecialchars($g['libelle']);
            $selected = ($lib === $genreChoisi) ? 'selected' : '';
            $html .= "<option value='{$lib}' {$selected}>{$lib}</option>";
        }
        $html .= "</select>

                <select name='public' class='select-public'>
                    <option value=''>-- Tous les publics --</option>";
        foreach ($publics as $p) {
            $lib = htmlspecialchars($p['libelle']);
            $selected = ($lib === $publicChoisi) ? 'selected' : '';
            $html .= "<option value='{$lib}' {$selected}>{$lib}</option>";
        }
        $html .= "</select>

                <button type='submit' class='btn-apply'>Appliquer</button>
            </div>
        </form>
        ";

        // === AFFICHAGE DES SÉRIES ===
        if (empty($results)) {
            $html .= "<p>Aucune série trouvée.</p>";
        } else {
            $html .= "<div class='series-grid'>";
            foreach ($results as $data) {
                $titre = htmlspecialchars($data['titre_serie']);
                $id = (int)$data['id_serie'];
                $nbEp = (int)$data['nb_episodes'];
                $moy = $data['moy'] ? round($data['moy'], 1) : '–';
                $image = htmlspecialchars($data['img'] ?? 'a.png');
                $html .= "
                    <div class='serie-card'>
                        <a href='?action=AfficherSerie&id={$id}'>
                            <img src='../../../img/{$image}' alt='Image de la série {$titre}' class='serie-img'>
                        </a>
                        <div class='serie-info'>
                            <a href='?action=AfficherSerie&id={$id}'><strong>{$titre}</strong></a>
                            <p>{$nbEp} épisode(s)</p>
                            <p>Note moyenne : <strong>{$moy}</strong></p>
                        </div>
                    </div>
                ";
            }
            $html .= "</div>";
        }

        return $html;
    }
}
