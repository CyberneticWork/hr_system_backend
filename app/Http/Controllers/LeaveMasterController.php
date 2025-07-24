<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\leave_master;
use App\Models\employee;
use Illuminate\Support\Facades\Validator;



class LeaveMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaveMasters = leave_master::with('employee')->get();
        return response()->json($leaveMasters);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'reporting_date' => 'required|date',
            'leave_type' => 'required|string|max:255',
            'leave_date' => 'nullable|date',
            'leave_from' => 'nullable|date',
            'leave_to' => 'nullable|date|after_or_equal:leave_from',
            'period' => 'nullable|string|max:255',
            'cancel_from' => 'nullable|date',
            'cancel_to' => 'nullable|date|after_or_equal:cancel_from',
            'reason' => 'nullable|string|max:1000',
            'status' => 'required|in:Pending,Approved,HR_Approved,Rejected'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $leaveMaster = leave_master::create($request->all());
        return response()->json($leaveMaster, 201);
    }

    /**
     * Display the specified resource.
     */

    public function show(string $id)
    {
        $leaveMaster = leave_master::where("employee_id", $id)->get();
        return response()->json($leaveMaster);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|exists:employees,id',
            'reporting_date' => 'sometimes|date',
            'leave_type' => 'sometimes|string|max:255',
            'leave_date' => 'nullable|date',
            'leave_from' => 'sometimes|date',
            'leave_to' => 'sometimes|date|after_or_equal:leave_from',
            'period' => 'nullable|string|max:255',
            'cancel_from' => 'nullable|date',
            'cancel_to' => 'nullable|date|after_or_equal:cancel_from',
            'reason' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:Pending,Approved,HR_Approved,Rejected'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $leaveMaster = leave_master::findOrFail($id);
        $leaveMaster->update($request->all());
        return response()->json($leaveMaster);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $leaveMaster = leave_master::findOrFail($id);
        $leaveMaster->delete();
        return response()->json(null, 204);
    }

    //return annual/casual/special leave record counts for a specific employee
    public function getLeaveRecordCountsByEmployee($employeeId)
    {
        $leaveCounts = leave_master::where('employee_id', $employeeId)
            ->selectRaw('leave_type, COUNT(*) as count')
            ->groupBy('leave_type')
            ->get();

        return response()->json($leaveCounts);
    }

}
