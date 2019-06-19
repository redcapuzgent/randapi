import {RandApiAction} from "./RandApiAction";

export class UndoRandomizationAction extends RandApiAction{
    /**
     *
     * @param parameters the recordid
     * @param token
     */
    constructor(public parameters:string, public token:string){
        super("undoRandomization",token,parameters);
    }
}