import {RandApiAction} from "./RandApiAction";
import {RandomizeRecordParameters} from "./RandomizeRecordParameters";

export class RandomizeRecordAction extends RandApiAction{
    public parameters: RandomizeRecordParameters;

    constructor(parameters:RandomizeRecordParameters){
        super("randomizeRecord", parameters);
    }
}