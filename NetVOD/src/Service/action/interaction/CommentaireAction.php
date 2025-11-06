<?php


namespace Service\action\interaction;

use Service\action\Action;
use Service\repository\DeefyRepository;

class CommentaireAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        if (!isset($_GET['id'])) {
            return "<p>Aucune Episode selectionne</p>";
        }

        $idEpisode = (int)$_GET['id'];
        $idProfil = (int) $_SESSION['profil']['id_profil'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        $check = $pdo->prepare("SELECT * FROM commentaire WHERE id_profil = ? AND id_episode = ?" );
        $check->execute([$idProfil,$idEpisode]);
        $com = $check->fetchAll();

        if ($com) {
            foreach ($com as $co) {
                $idCom = $co['id_commentaire'];
                return "<div class='commentaire-detail'><p>Voici votre commentaire :</p>
            <p>Commentaire : {$co['texte']}</p>
            <p>Note : {$co['note']}</p>
            <p><a href='?action=supprimerCom&id={$idCom}&idEp={$idEpisode}'>Supprimer le commentaire</a></p>
            <p><a href='?action=AfficherEpisode&id={$idEpisode}'>Retour à l'épisode</a></p></div>";
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $html = "
        <div class='commentaire-detail'>
            <h2 class='text-2xl font-bold mb-4'>Laisser un commentaire</h2>
                <form method='post' action='?action=Commentaire&id={$idEpisode}' class='space-y-4'>
                    <div>
                        <label for='comm' class='block font-semibold'>Commentaire :</label>
                        <textarea id='comm' name='comm' required
                            class='w-full border px-3 py-2 rounded h-32 resize-y'
                            placeholder='Écris ton commentaire ici...'></textarea>
                    </div>
                    </br>
                    <div>
                        <label for='note' class='block font-semibold'>Note (sur 20) :</label>
                        <input id='note' name='note' type='number' min='0' max='20' step='1' required
                               class='w-full border px-3 py-2 rounded'
                               placeholder='Entrez une note entre 0 et 20'>
                    </div>
                    </br>
                    <button type='submit' class='w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition'>
                        Publier
                    </button>
                </form>
            </div>
        ";
        }else{
            $commentaire = $_POST['comm'];
            $note = $_POST['note'];
            $check = $pdo->prepare("
                INSERT INTO commentaire(id_profil,id_episode,texte,note)
                VALUES (?,?,?,?)
            ");
            $check->execute([$idProfil,$idEpisode,$commentaire,$note]);
            $idCom = $pdo->lastInsertId();
            $html = "<div class='commentaire-detail'><p>Voici votre commentaire :</p>
                    <p>$commentaire</p>
                    <p><a href='?action=supprimerCom&id={$idCom}&idEp={$idEpisode}'>Supprimer le commentaire</a></p>
                    <p><a href='?action=AfficherEpisode&id={$idEpisode}'>Retour à l'épisode</a></p></div>";
        }

        return $html;
    }
}
