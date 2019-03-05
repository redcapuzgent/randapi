import {RandApiAction} from "./RandApiAction";
import {RandomizeRecordParameters} from "./RandomizeRecordParameters";

export class RandomizeRecordAction extends RandApiAction{

    constructor(public parameters:RandomizeRecordParameters, public token: string){
        super("randomizeRecord",token, parameters);
    }
}