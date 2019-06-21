import {RandApiAction} from "./RandApiAction";
import {ChangeSourcesParameters} from "./ChangeSourcesParameters";

export class ChangeSourcesAction extends RandApiAction{

    constructor(public parameters:ChangeSourcesParameters, public token: string){
        super("changeSources",token, parameters);
    }
}