<?php

namespace Service\action;

abstract class Action {

    protected ?string $http_method = null;
    protected ?string $hostname = null;
    protected ?string $script_name = null;

    protected string $result = "";

    public function __construct() {
        $this->http_method = $_SERVER['REQUEST_METHOD'];
        $this->hostname = $_SERVER['HTTP_HOST'];
        $this->script_name = $_SERVER['SCRIPT_NAME'];

        $this->result = $this->execute();
    }

    public function execute(): string {
        if ($this->http_method === 'POST') {
            return $this->post();
        } else {
            return $this->get();
        }
    }

    // Chaque Service.action doit définir ce qu’elle fait dans le GET et dans le POST
    abstract protected function get(): string;
    abstract protected function post(): string;

    // permet de récupérer le résultat
    public function getResult(): string {
        return $this->result;
    }
}
