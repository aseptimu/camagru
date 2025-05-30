<?php
namespace Camagru\Exception;

class ValidationException extends ApiException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}