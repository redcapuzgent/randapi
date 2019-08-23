import {RandomizationAllocation} from "./RandomizationAllocation";

export class ChangeTargetParameters{

    /**
     *
     * @param recordId The record that we want to randomize
     * @param target The new target
     * @param allocations allocations New allocations in case none are available for the given target and current sources
     * @param groupId (optional) The DAG identifier. default = '' (none)
     * @param armName (optional) The name of the arm. default = 'Arm 1'
     * @param eventName (optional) The name of the event. default = 'Event 1'
     */
    constructor(public recordId: string,
                public target: string,
                public allocations: RandomizationAllocation[],
                public groupId: string = '',
                public armName: string= 'Arm 1',
                public eventName: string = 'Event 1') {
    }
}