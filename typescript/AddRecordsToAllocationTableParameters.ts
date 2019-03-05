import {RandomizationAllocation} from "./RandomizationAllocation";

export interface AddRecordsToAllocationTableParameters{
    project_status: number;
    allocations: RandomizationAllocation[];
}