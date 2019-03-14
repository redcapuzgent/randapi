<?php

namespace redcapuzgent\Randapi\model;

use \Exception;
use \JsonSerializable;
use \Throwable;

class RandapiException extends Exception implements JsonSerializable
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function jsonSerialize()
    {
        return [
            "message"=>$this->message,
            "code"=>$this->code,
            "previous"=>!is_null($this->getPrevious())?$this->getPrevious()->getMessage()." ".$this->getPrevious()->getTraceAsString():null
        ];
    }
}