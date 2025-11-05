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

        // Episodes en cours
        $stmt = $pdo->prepare("
            SELECT e.id_episode, e.titre, e.img, s.titre_serie
            FROM en_cours ec
            JOIN episode e ON ec.id_episode = e.id_episode
            JOIN serie s ON e.id_serie = s.id_serie
            WHERE ec.id_profil = :id_profil
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $episodes = $stmt->fetchAll();

        // Favoris
        $stmt = $pdo->prepare("
            SELECT s.id_serie, s.titre_serie, s.img
            FROM favoris f
            JOIN serie s ON f.id_serie = s.id_serie
            WHERE f.id_profil = :id_profil
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $favoris = $stmt->fetchAll();

        $html = "<h2>Bienvenue, {$_SESSION['user']['email']} !</h2>";
        $html .= "<h3>Profil actuel : <strong>{$username}</strong></h3>";

        // Affichage épisodes en cours
        $html .= "<h3>Épisodes en cours :</h3><div class='series-grid'>";
        foreach ($episodes as $ep) {
            $img = $ep['img'] ?: 'a.jpg';
            $html .= "<div class='serie-card'>
                        <img src='../../../img/{$img}' class='serie-img'>
                        <a href='?action=afficherEpisode&id={$ep['id_episode']}'>{$ep['titre_serie']} - {$ep['titre']}</a>
                      </div>";
        }
        $html .= "</div>";

        // Affichage favoris
        $html .= "<h3>Séries favorites :</h3><div class='series-grid'>";
        foreach ($favoris as $f) {
            $img = $f['img'] ?: 'a.jpg';
            $html .= "<div class='serie-card'>
                        <img src='../../../img/{$img}' class='serie-img'>
                        <a href='?action=afficherSerie&id={$f['id_serie']}'>{$f['titre_serie']}</a>
                      </div>";
        }
        $html .= "</div>";

        return $html;
    }
}
