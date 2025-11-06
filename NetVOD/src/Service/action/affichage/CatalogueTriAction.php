<?php

namespace Service\action\affichage;

use Service\action\Action;
use Service\repository\DeefyRepository;

class CatalogueTriAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        $pdo = DeefyRepository::getInstance()->getPDO();

        $motCle = $_GET['search'] ?? '';
        $tri = $_GET['tri'] ?? 'titre_serie';
        $ordre = $_GET['ordre'] ?? 'ASC';

        // ✅ Autorise bien "moy" et non "moyenne"
        $triValides = ['titre_serie', 'date_ajout', 'nb_episodes', 'moy'];
        $ordreValides = ['ASC', 'DESC'];

        if (!in_array($tri, $triValides)) $tri = 'titre_serie';
        if (!in_array($ordre, $ordreValides)) $ordre = 'ASC';

        $sql = "
            SELECT s.id_serie, s.titre_serie, s.date_ajout,
                   COUNT(e.id_episode) AS nb_episodes,
                   AVG(c.note) AS moy
            FROM serie s
            LEFT JOIN episode e ON s.id_serie = e.id_serie
            LEFT JOIN commentaire_serie c ON s.id_serie = c.id_serie
        ";

        if (!empty($motCle)) {
            $sql .= " WHERE s.titre_serie LIKE :motCle OR s.descriptif LIKE :motCle";
        }

        $sql .= " GROUP BY s.id_serie ORDER BY $tri $ordre";

        $stmt = $pdo->prepare($sql);
        if (!empty($motCle)) $stmt->execute([':motCle' => "%$motCle%"]);
        else $stmt->execute();

        $results = $stmt->fetchAll();

        // === HTML ===
        $html = "
        <h2>Catalogue des séries</h2>
        <div class='catalogue-controls'>
            <form method='get' action='' class='catalogue-form'>
                <input type='hidden' name='action' value='CatalogueTri'>
                <input type='text' name='search' placeholder='🔍 Rechercher...' value='" . htmlspecialchars($motCle) . "'>

                <select name='tri'>
                    <option value='titre_serie'" . ($tri === 'titre_serie' ? ' selected' : '') . ">Titre</option>
                    <option value='date_ajout'" . ($tri === 'date_ajout' ? ' selected' : '') . ">Date d’ajout</option>
                    <option value='nb_episodes'" . ($tri === 'nb_episodes' ? ' selected' : '') . ">Nombre d’épisodes</option>
                    <option value='moy'" . ($tri === 'moy' ? ' selected' : '') . ">Note moyenne</option>
                </select>

                <select name='ordre'>
                    <option value='ASC'" . ($ordre === 'ASC' ? ' selected' : '') . ">Croissant</option>
                    <option value='DESC'" . ($ordre === 'DESC' ? ' selected' : '') . ">Décroissant</option>
                </select>

                <button type='submit'>Appliquer</button>
            </form>
        </div>
        ";

        if (empty($results)) {
            $html .= "<p>Aucune série trouvée.</p>";
        } else {
            $html .= "<div class='series-grid'>";
            foreach ($results as $data) {
                $titre = htmlspecialchars($data['titre_serie']);
                $id = (int)$data['id_serie'];
                $nbEp = (int)$data['nb_episodes'];
                $moy = $data['moy'] ? round($data['moy'], 1) : '–';

                $html .= "
                    <div class='serie-card'>
                        <img src='../../../img/a.jpg' alt='Image de la série {$titre}' class='serie-img'>
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
