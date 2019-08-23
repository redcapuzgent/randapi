import {RandApiAction} from "./RandApiAction";
import {ChangeTargetParameters} from "./ChangeTargetParameters";

export class ChangeTargetAction extends RandApiAction{

    constructor(public parameters:ChangeTargetParameters, public token: string){
        super("changeTarget",token, parameters);
    }
}