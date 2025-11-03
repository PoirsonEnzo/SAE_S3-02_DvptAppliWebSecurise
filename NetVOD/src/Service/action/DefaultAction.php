<?php
namespace Service\action;
class DefaultAction extends Action
{

    public function getResult(): string
    {
        return "Bienvenue !";
    }
}