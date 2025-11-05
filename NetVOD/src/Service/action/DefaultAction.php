<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class DefaultAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<h2>Bienvenue sur NetVOD !</h2>
                    <p><a href="?action=SignIn" class="text-blue-500">Se connecter</a> ou 
                    <a href="?action=AddUser" class="text-blue-500">S’inscrire</a></p>';
        }

        $pdo = DeefyRepository::getInstance()->getPDO();
        $idProfil = $_SESSION['profil']['id_profil'] ?? null;
        $username = htmlspecialchars($_SESSION['profil']['username'] ?? '');

        if (!$idProfil) {
            return "<p>Aucun profil actif. <a href='?action=ChoisirProfilAction'>Choisir un profil</a></p>";
        }

        // --- Épisodes en cours ---
        $stmt = $pdo->prepare("
            SELECT e.id_episode, e.titre, e.img, s.titre_serie
            FROM en_cours ec
            JOIN episode e ON ec.id_episode = e.id_episode
            JOIN serie s ON e.id_serie = s.id_serie
            WHERE ec.id_profil = :id_profil
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $episodes = $stmt->fetchAll();

        // --- Favoris ---
        $stmt = $pdo->prepare("
            SELECT s.id_serie, s.titre_serie, s.img
            FROM favoris f
            JOIN serie s ON f.id_serie = s.id_serie
            WHERE f.id_profil = :id_profil
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $favoris = $stmt->fetchAll();

        // --- Total épisodes ---
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT e.id_episode) AS total_episodes,
                e.id_serie,
                s.titre_serie,
                s.img
            FROM episode e
            INNER JOIN serie s 
                ON s.id_serie = e.id_serie
            GROUP BY e.id_serie,s.titre_serie,s.img
        ");
        $stmt->execute();
        $totalep = $stmt->fetchAll();

        // --- Déjà visionnées ---
        $stmt = $pdo->prepare("
            SELECT 
                e.id_serie,
                COUNT(DISTINCT v.id_episode) AS episodes_vus
            FROM episode e
            INNER JOIN visionnees v 
                ON v.id_episode = e.id_episode
            WHERE v.id_profil = ?
            GROUP BY e.id_serie
        ");
        $stmt->execute([$idProfil]);
        $vision = $stmt->fetchAll();

        // --- Compte actif en haut à droite ---


        $html = "<h2>Bienvenue, {$_SESSION['user']['email']} !</h2>";

        // --- Affichage épisodes en cours ---
        $html .= "<h3>Épisodes en cours :</h3><div class='series-grid'>";
        foreach ($episodes as $ep) {
            $img = $ep['img'] ?: 'a.jpg';
            $html .= "<div class='serie-card'>
                        <a href='?action=AfficherEpisode&id={$ep['id_episode']}'>
                            <img src='../../../img/{$img}' class='serie-img' alt='{$ep['titre_serie']} - {$ep['titre']}'>
                        </a>
                        <a href='?action=AfficherEpisode&id={$ep['id_episode']}'>{$ep['titre_serie']} - {$ep['titre']}</a>
                      </div>";
        }
        $html .= "</div>";

        // --- Affichage favoris ---
        $html .= "<h3>Séries favorites :</h3><div class='series-grid'>";
        foreach ($favoris as $f) {
            $img = $f['img'] ?: 'a.jpg';
            $html .= "<div class='serie-card'>
                        <a href='?action=AfficherSerie&id={$f['id_serie']}'>
                            <img src='../../../img/{$img}' class='serie-img' alt='{$f['titre_serie']}'>
                        </a>
                        <a href='?action=AfficherSerie&id={$f['id_serie']}'>{$f['titre_serie']}</a>
                        <div class='etoile'>
                            <a href='?action=SupFavoris&id={$f['id_serie']}'>
                                ⭐
                            </a>
                        </div>
                      </div>";
        }
        $html .= "</div>";

        // --- Affichage déjà visionnées ---
        $html .= "<h3>Séries déjà visionnées :</h3><div class='series-grid'>";
        foreach ($totalep as $te) {
            foreach ($vision as $v) {
                if ($te['id_serie'] == $v['id_serie'] && $te['total_episodes'] == $v['episodes_vus']) {
                    $img = $te['img'] ?: 'a.jpg';
                    $html .= "<div class='serie-card'>
                                <a href='?action=AfficherSerie&id={$te['id_serie']}'>
                                    <img src='../../../img/{$img}' class='serie-img' alt='{$te['titre_serie']}'>
                                </a>
                                <a href='?action=AfficherSerie&id={$te['id_serie']}'>{$te['titre_serie']}</a>
                              </div>";
                }
            }
        }
        $html .= "</div>";

        return $html;
    }
}
