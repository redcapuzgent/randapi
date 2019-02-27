import {RandApiAction} from "./RandApiAction";
import {AddRecordsToAllocationTableParameters} from "./AddRecordsToAllocationTableParameters";

export class AddRecordsToAllocationTableAction extends RandApiAction{
    public parameters: AddRecordsToAllocationTableParameters;

    constructor(parameters:AddRecordsToAllocationTableParameters){
        super("addRecordsToAllocationTable", parameters);
    }
}