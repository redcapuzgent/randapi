export abstract class RandApiAction{
    public action: string;
    public parameters: any;

    constructor(action:string, parameters:any){
        this.action = action;
        this.parameters = parameters;
    }
}