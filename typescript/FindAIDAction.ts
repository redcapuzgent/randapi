import {RandApiAction} from "./RandApiAction";

export class FindAIDAction extends RandApiAction{
    /**
     *
     * @param parameters The recordid
     * @param token
     */
    constructor(public parameters:string, public token:string){
        super("findAID",token,parameters);
    }
}