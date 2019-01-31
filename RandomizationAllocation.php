<?php

namespace redcapuzgent\Randapi;

class RandomizationAllocation
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
        $this->target_field = $target_field;
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




}