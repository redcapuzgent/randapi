<?php

namespace redcapuzgent\Randapi;

require_once 'RandapiException.php';

use stdClass;

class RandomizationAllocation implements \JsonSerializable
{
    /**
     * @var string[]
     */
    private $source_fields;

    /**
     * @var string
     */
    private $target_field;

    /**
     * RandomizationAllocation constructor.
     * @param string[] $source_fields Ordered list of source_field values
     * @param string $target_field The value for the target_field
     * @throws Exception
     */
    public function __construct(array $source_fields, string $target_field)
    {
        if(is_null($source_fields) || sizeof($source_fields) < 1 || sizeof($source_fields) > 15){
            throw new Exception("Invalid source_fields parameter");
        }
        if(is_null($target_field)){
            throw new Exception("Invalid target_field parameter");
        }
        $this->source_fields = $source_fields;
        $this->target_field = db_real_escape_string($target_field);
    }

    /**
     * @return string[]
     */
    public function getSourceFields(): array
    {
        return $this->source_fields;
    }

    /**
     * @return string
     */
    public function getTargetField(): string
    {
        return $this->target_field;
    }

    /**
     * @return string[]
     */
    public function getSourceFieldNames(){
        $sourceFieldNames = array();
        for ($i = 0; $i < sizeof($this->getSourceFields()); $i++) {
            $sourceFieldNames[$i] = "source_field" . ($i + 1);
        }
        return $sourceFieldNames;
    }

    /**
     * @return string[]
     */
    public function getSourceFieldValues(){
        $sourceFieldValues = array();
        foreach($this->getSourceFields() as $sourceField){
            array_push($sourceFieldValues, db_real_escape_string($sourceField));
        }
        return $sourceFieldValues;
    }

    /**
     * @param stdClass $in
     * @return RandomizationAllocation
     * @throws \RandapiException
     */
    public static function fromstdClass(stdClass $in){
        if(property_exists($in,"source_fields") &&
            property_exists($in,"target_field")){
            return new RandomizationAllocation($in->source_fields, $in->target_field);
        }else{
            throw new \RandapiException("Could not create RandomizationAllocation. Object does not have properties source_fields and target_field");
        }
    }

    public function jsonSerialize()
    {
        return [
            "source_fields"=>$this->getSourceFields(),
            "target_field"=>$this->getTargetField()
        ];
    }


}