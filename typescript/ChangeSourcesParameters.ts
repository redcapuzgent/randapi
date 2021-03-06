import {RandomizationField} from "./RandomizationField";
import {RandomizationAllocation} from "./RandomizationAllocation";

export class ChangeSourcesParameters{

    /**
     *
     * @param recordId The record that we want to randomize
     * @param fields An array of RandomizationFields
     * @param allocations New allocations in case none are available for the given fields and current target
     * @param groupId (optional) The DAG identifier. default = '' (none)
     * @param armName (optional) The name of the arm. default = 'Arm 1'
     * @param eventName (optional) The name of the event. default = 'Event 1'
     */
    constructor(public recordId: string,
                public fields: RandomizationField[],
                public allocations: RandomizationAllocation[],
                public groupId: string = '',
                public armName: string= 'Arm 1',
                public eventName: string = 'Event 1') {
    }
}