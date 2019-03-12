<?php

namespace redcapuzgent\Randapi\model;

use \JsonSerializable;

class RandomizationField implements JsonSerializable
{
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $value;

    /**
     * RandomizationField constructor.
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public static function fromStdClass(\stdClass $in){
        if(property_exists($in,"key") &&
            property_exists($in,"value")){
            return new RandomizationField($in->key, $in->value);
        }else{
            throw new RandapiException("Could not create RandomizationField. Object does not have properties key and value");
        }
    }

    public function jsonSerialize()
    {
        return [
            "key"=>$this->getKey(),
            "value"=>$this->getValue()
        ];
    }

}