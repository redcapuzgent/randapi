import {RandApiAction} from "./RandApiAction";
import {RandomizationAllocation} from "./RandomizationAllocation";

export class AvailableSlotsAction extends RandApiAction{

    constructor(public parameters:RandomizationAllocation, public token: string){
        super("availableSlots",token, parameters);
    }
}