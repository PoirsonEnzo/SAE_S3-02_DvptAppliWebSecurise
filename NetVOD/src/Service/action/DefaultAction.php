<?php

namespace Service\action;

class DefaultAction extends Action {

    protected function get(): string {
        return <<<HTML
        <h1>Bienvenue sur NetVOD </h1>
        HTML;
    }

    protected function post(): string {
        return $this->get();
    }
}
