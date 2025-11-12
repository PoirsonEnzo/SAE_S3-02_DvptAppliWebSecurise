<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class DefaultAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return <<<HTML
            <div class="center-message">
                <h2>Il faut se connecter </h2>
                <div class="btn-container">
                    <a href="?action=SignIn" class="btn-center">Se connecter</a>
                    <a href="?action=AddUser" class="btn-center">S’inscrire</a>
                </div>
            </div>
HTML;
        }

        $idProfil = $_SESSION['profil']['id_profil'] ?? null;

        if (!$idProfil) {
            return <<<HTML
            <div class="center-message">
                <h2>Aucun profil actif</h2>
                <div class="btn-container">
                    <a href="?action=AddProfilAction" class="btn-center">Créer un profil</a>
                    <a href="?action=ChoisirProfilAction" class="btn-center">Choisir un profil existant</a>
                </div>
            </div>
HTML;
        }

        $pdo = DeefyRepository::getInstance()->getPDO();

        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $imgPrefix = $baseUrl . '/img/';

        // --- Séries terminées (toutes les séries dont tous les épisodes ont été vus) ---
        $stmt = $pdo->prepare("
            SELECT s.id_serie
            FROM serie s
            INNER JOIN episode e ON s.id_serie = e.id_serie
            LEFT JOIN visionnees v 
                ON v.id_episode = e.id_episode AND v.id_profil = :id_profil
            GROUP BY s.id_serie
            HAVING COUNT(DISTINCT e.id_episode) = COUNT(DISTINCT v.id_episode)
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $seriesTerminees = array_column($stmt->fetchAll(), 'id_serie');

        // --- Épisodes en cours (on exclut les séries terminées) ---
        $stmt = $pdo->prepare("
            SELECT e.id_episode, e.titre, e.img, s.titre_serie, s.id_serie
            FROM en_cours ec
            JOIN episode e ON ec.id_episode = e.id_episode
            JOIN serie s ON e.id_serie = s.id_serie
            WHERE ec.id_profil = :id_profil
              AND s.id_serie NOT IN (
                    SELECT s2.id_serie
                    FROM serie s2
                    INNER JOIN episode e2 ON s2.id_serie = e2.id_serie
                    LEFT JOIN visionnees v2 
                        ON v2.id_episode = e2.id_episode AND v2.id_profil = :id_profil
                    GROUP BY s2.id_serie
                    HAVING COUNT(DISTINCT e2.id_episode) = COUNT(DISTINCT v2.id_episode)
              )
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $episodes = $stmt->fetchAll();

        // --- Séries favorites ---
        $stmt = $pdo->prepare("
            SELECT s.id_serie, s.titre_serie, s.img
            FROM favoris f
            JOIN serie s ON f.id_serie = s.id_serie
            WHERE f.id_profil = :id_profil
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $favoris = $stmt->fetchAll();

        // --- Séries déjà visionnées (toutes terminées) ---
        $stmt = $pdo->prepare("
            SELECT s.id_serie, s.titre_serie, s.img
            FROM serie s
            WHERE s.id_serie IN (
                SELECT s2.id_serie
                FROM serie s2
                INNER JOIN episode e2 ON s2.id_serie = e2.id_serie
                LEFT JOIN visionnees v2 
                    ON v2.id_episode = e2.id_episode AND v2.id_profil = :id_profil
                GROUP BY s2.id_serie
                HAVING COUNT(DISTINCT e2.id_episode) = COUNT(DISTINCT v2.id_episode)
            )
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $seriesVisionnees = $stmt->fetchAll();

        // --- HTML ---
        $html = "<h2>Bienvenue, " . htmlspecialchars($_SESSION['user']['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . " !</h2>";

        // --- Épisodes en cours ---
        $html .= "<h3>Épisodes en cours :</h3><div class='series-grid'>";
        if (empty($episodes)) {
            $html .= "<p>Aucun épisode en cours.</p>";
        } else {
            foreach ($episodes as $ep) {
                $img = htmlspecialchars($ep['img'] ?? 'a.jpg', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $titreSerie = htmlspecialchars($ep['titre_serie'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $titreEp = htmlspecialchars($ep['titre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                // lien direct vers l’épisode
                $html .= "<div class='serie-card'>
                            <a href='?action=AfficherEpisode&id={$ep['id_episode']}'>
                                <img src='{$imgPrefix}{$img}' class='serie-img' alt='{$titreSerie} - {$titreEp}'>
                            </a>
                            <a href='?action=AfficherEpisode&id={$ep['id_episode']}'>{$titreSerie} - {$titreEp}</a>
                          </div>";
            }
        }
        $html .= "</div>";

        // --- Séries favorites ---
        $html .= "<h3>Séries favorites :</h3><div class='series-grid'>";
        foreach ($favoris as $f) {
            $img = htmlspecialchars($f['img'] ?? 'a.jpg', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $titreSerie = htmlspecialchars($f['titre_serie'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= "<div class='serie-card'>
                        <a href='?action=AfficherSerie&id={$f['id_serie']}'>
                            <img src='{$imgPrefix}{$img}' class='serie-img' alt='{$titreSerie}'>
                        </a>
                        <a href='?action=AfficherSerie&id={$f['id_serie']}'>{$titreSerie}</a>
                        <div class='etoile'>
                            <a href='?action=SupFavoris&id={$f['id_serie']}'>⭐</a>
                        </div>
                      </div>";
        }
        $html .= "</div>";

        // --- Séries déjà visionnées ---
        $html .= "<h3>Séries déjà visionnées :</h3><div class='series-grid'>";
        foreach ($seriesVisionnees as $sv) {
            $img = htmlspecialchars($sv['img'] ?? 'a.jpg', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $titreSerie = htmlspecialchars($sv['titre_serie'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= "<div class='serie-card'>
                        <a href='?action=AfficherSerie&id={$sv['id_serie']}'>
                            <img src='{$imgPrefix}{$img}' class='serie-img' alt='{$titreSerie}'>
                        </a>
                        <a href='?action=AfficherSerie&id={$sv['id_serie']}'>{$titreSerie}</a>
                      </div>";
        }
        $html .= "</div>";

        return $html;
    }
}
