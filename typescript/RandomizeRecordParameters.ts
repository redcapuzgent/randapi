import {RandomizationField} from "./RandomizationField";

export class RandomizeRecordParameters{

    /**
     *
     * @param recordId The record that we want to randomize
     * @param projectId The projectId where the record belongs to
     * @param fields An array of RandomizationFields
     * @param resultFieldName The field where the randomization result can be stored.
     * @param groupId (optional) The DAG identifier. default = '' (none)
     * @param armName (optional) The name of the arm. default = 'Arm 1'
     * @param eventName (optional) The name of the event. default = 'Event 1'
     */
    constructor(private recordId: string, private projectId: number, private fields: RandomizationField[],
                private resultFieldName: string, private groupId: string = '',
                private armName: string= 'Arm 1', private eventName: string = 'Event 1') {
    }


    getRecordId(): string {
        return this.recordId;
    }

    getProjectId(): number {
        return this.projectId;
    }

    getFields(): RandomizationField[] {
        return this.fields;
    }

    getResultFieldName(): string {
        return this.resultFieldName;
    }

    getGroupId(): string {
        return this.groupId;
    }

    getArmName(): string {
        return this.armName;
    }

    getEventName(): string {
        return this.eventName;
    }
}