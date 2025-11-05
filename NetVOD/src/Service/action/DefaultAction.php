<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class DefaultAction extends Action
{
    public function getResult(): string
    {
        // Vérifie si un utilisateur est connecté
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

        // Récupère les épisodes "en cours" pour ce profil
        $stmt = $pdo->prepare("
            SELECT e.id_episode, e.titre, e.numero_episode, s.titre_serie
            FROM en_cours ec
            JOIN episode e ON ec.id_episode = e.id_episode
            JOIN serie s ON e.id_serie = s.id_serie
            WHERE ec.id_profil = :id_profil
            ORDER BY s.titre_serie, e.numero_episode
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $episodes = $stmt->fetchAll();

        // Récupère les séries favorites pour ce profil
        $stmt = $pdo->prepare("
            SELECT s.id_serie, s.titre_serie, s.descriptif
            FROM favoris f
            JOIN serie s ON f.id_serie = s.id_serie
            WHERE f.id_profil = :id_profil
            ORDER BY s.titre_serie
        ");
        $stmt->execute(['id_profil' => $idProfil]);
        $favoris = $stmt->fetchAll();

        // Construction du HTML fusionné
        $html = "
        <div class='max-w-4xl mx-auto mt-8 p-6 bg-white shadow-md rounded-lg'>
            <h2 class='text-2xl font-bold mb-2'>Bienvenue, {$_SESSION['user']['email']} !</h2>
            <h3 class='text-lg text-gray-700 mb-6'>Profil actuel : <strong>{$username}</strong></h3>";

        // 🔹 Épisodes en cours
        $html .= "<h3 class='text-xl font-semibold mb-4'>Vos épisodes en cours :</h3>";
        if (!$episodes) {
            $html .= "<p class='text-gray-600'>Vous n'avez aucun épisode en cours pour ce profil.</p>";
        } else {
            $html .= "<ul class='space-y-3'>";
            foreach ($episodes as $ep) {
                $titreSerie = htmlspecialchars($ep['titre_serie']);
                $titreEpisode = htmlspecialchars($ep['titre']);
                $numero = htmlspecialchars($ep['numero_episode']);
                $idEp = (int) $ep['id_episode'];

                $html .= "
                <li class='border p-3 rounded hover:bg-gray-100 transition'>
                    <strong class='text-blue-600'>{$titreSerie}</strong> — Épisode {$numero} :
                    <a href='?action=afficherEpisode&id={$idEp}' class='text-blue-500 hover:underline'>{$titreEpisode}</a>
                </li>";
            }
            $html .= "</ul>";
        }

        // 🔹 Favoris
        $html .= "<h3 class='text-xl font-semibold mt-6 mb-4'>Vos séries favorites :</h3>";
        if (!$favoris) {
            $html .= "<p class='text-gray-600'>Vous n'avez aucune série en favoris pour ce profil.</p>";
        } else {
            $html .= "<ul class='space-y-3'>";
            foreach ($favoris as $f) {
                $titreSerie = htmlspecialchars($f['titre_serie']);
                $idSerie = (int)$f['id_serie'];
                $descriptif = htmlspecialchars($f['descriptif'] ?? '');
                $html .= "
                <li class='border p-3 rounded hover:bg-gray-100 transition'>
                    <a href='?action=afficherSerie&id={$idSerie}' class='text-blue-500 hover:underline'>
                        <strong>{$titreSerie}</strong>
                    </a>
                    <p class='text-gray-600'>{$descriptif}</p>
                </li>";
            }
            $html .= "</ul>";
        }

        $html .= "</div>";

        return $html;
    }
}
