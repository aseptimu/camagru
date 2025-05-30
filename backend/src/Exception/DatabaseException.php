<?php
namespace Camagru\Exception;

use Exception;

class DatabaseException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, 500);
    }
}