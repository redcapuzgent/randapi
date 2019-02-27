import {RandomizationAllocation} from "./RandomizationAllocation";

export interface AddRecordsToAllocationTableParameters{
    projectId: number;
    project_status: number;
    allocations: RandomizationAllocation[];
}