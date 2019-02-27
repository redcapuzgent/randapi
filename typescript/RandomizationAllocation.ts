export class RandomizationAllocation
{
    private source_fields: string[];
    private target_field: string;

    /**
     *
     * @param source_fields Ordered list of source_field values. A maximum of 15 items is allowed.
     * @param target_field The value for the target_field
     * @constructor
     */
    constructor(source_fields: string[], target_field: string)
    {
        if(!source_fields || source_fields.length < 1 || source_fields.length > 15){
        throw new Error("Invalid source_fields parameter");
        }
        if(!target_field){
            throw new Error("Invalid target_field parameter");
        }
        this.source_fields = source_fields;
        this.target_field = target_field;
    }

    public getSourceFields(): string[]{
        return this.source_fields;
    }

    public getTargetField(): string{
        return this.target_field;
    }

}