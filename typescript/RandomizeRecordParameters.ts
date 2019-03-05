import {RandomizationField} from "./RandomizationField";

export class RandomizeRecordParameters{

    /**
     *
     * @param recordId The record that we want to randomize
     * @param fields An array of RandomizationFields
     * @param resultFieldName The field where the randomization result can be stored.
     * @param groupId (optional) The DAG identifier. default = '' (none)
     * @param armName (optional) The name of the arm. default = 'Arm 1'
     * @param eventName (optional) The name of the event. default = 'Event 1'
     */
    constructor(public recordId: string, public fields: RandomizationField[],
                public resultFieldName: string, public groupId: string = '',
                public armName: string= 'Arm 1', public eventName: string = 'Event 1') {
    }
}