<?php

namespace Service\Exception;

use \Exception;

class InvalidPropertyNameException extends Exception
{

    /**
     * @param string $string
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

}