<?php

namespace Service\action;

class DefaultAction extends Action {

    protected function get(): string {
        return <<<HTML
        <h1>Bienvenue sur le Service de documentation Scientifique </h1>
        HTML;
    }

    protected function post(): string {
        return $this->get();
    }
}
