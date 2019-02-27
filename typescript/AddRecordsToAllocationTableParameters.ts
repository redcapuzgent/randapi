import {RandomizationAllocation} from "./RandomizationAllocation";

export interface AddRecordsToAllocationTableParameters{
    rid: number;
    project_status: number;
    allocations: RandomizationAllocation[];
}