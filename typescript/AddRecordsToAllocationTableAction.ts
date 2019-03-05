import {RandApiAction} from "./RandApiAction";
import {AddRecordsToAllocationTableParameters} from "./AddRecordsToAllocationTableParameters";

export class AddRecordsToAllocationTableAction extends RandApiAction{

    constructor(public parameters:AddRecordsToAllocationTableParameters, public token: string){
        super("addRecordsToAllocationTable",token, parameters);
    }
}