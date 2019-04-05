import {RandApiAction} from "./RandApiAction";

export class FindAIDAction extends RandApiAction{
    constructor(public parameters:string, public token:string){
        super("findAID",token,parameters);
    }
}