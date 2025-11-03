<?php
namespace Service\action;



abstract class Action
{
    abstract public function getResult(): string;

    protected function renderTracks(array $tracks): string
    {
        $html = '';

        foreach ($tracks as $track) {
            $title = htmlspecialchars($track->title ?? 'Sans titre');
            // Chemin vers le fichier
            $filePath = property_exists($track, 'filename') && $track->filename !== ''
                ? "/src/iutnc/uploads/{$track->filename}"
                : '#';

            $author = 'Inconnu';
            $html .= <<<HTML
            <div class="bg-white shadow-md rounded p-4 mb-4 flex justify-between items-center hover:shadow-lg transition">
                <div>
                    <h3 class="text-lg font-bold">{$title}</h3>
                    <p class="text-gray-600">Auteur : {$author}</p>
                </div>
                <audio controls class="w-48">
                    <source src="{$filePath}" type="audio/mpeg">
                    Votre navigateur ne supporte pas la lecture audio.
                </audio>
            </div>
HTML;
        }

        return $html;
    }
}
