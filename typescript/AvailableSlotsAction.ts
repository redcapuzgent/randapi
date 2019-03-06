import {RandApiAction} from "./RandApiAction";
import {AvailableSlotsParameters} from "./AvailableSlotsParameters";

export class AvailableSlotsAction extends RandApiAction{

    constructor(public parameters:AvailableSlotsParameters, public token: string){
        super("availableSlots",token, parameters);
    }
}