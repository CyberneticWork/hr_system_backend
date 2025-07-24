<?php

namespace App\Http\Controllers;

use App\Models\NoPayRecord;
use App\Models\Employee;
use App\Models\time_card;
use App\Models\leave_master;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NoPayController extends Controller
{
    public function index(Request $request)
    {
        $query = NoPayRecord::with(['employee', 'processedBy'])
            ->orderBy('date', 'desc');

        if ($request->has('month')) {
            $query->whereMonth('date', $request->month);
        }

        if ($request->has('year')) {
            $query->whereYear('date', $request->year);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'no_pay_count' => 'required|numeric|min:0.5|max:2',
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $noPayRecord = NoPayRecord::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'no_pay_count' => $request->no_pay_count,
            'description' => $request->description,
            'processed_by' => auth::id(),
        ]);

        return response()->json($noPayRecord, 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'no_pay_count' => 'sometimes|numeric|min:0.5|max:2',
            'description' => 'sometimes|string|max:500',
            'status' => 'sometimes|in:Pending,Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $noPayRecord = NoPayRecord::findOrFail($id);
        $noPayRecord->update($request->all());

        return response()->json($noPayRecord);
    }

    public function destroy($id)
    {
        $noPayRecord = NoPayRecord::findOrFail($id);
        $noPayRecord->delete();

        return response()->json(null, 204);
    }

    public function generateDailyNoPayRecords(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        
        // Get all active employees
        $employees = employee::where('is_active', '1')->get();
        
        $generatedRecords = [];
        
        foreach ($employees as $employee) {
            // Check if employee has time card for the date
            $hasTimeCard = time_card::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->exists();
                
            if ($hasTimeCard) {
                continue;
            }
            
            // Check if employee has approved leave for the date
            $hasLeave = leave_master::where('employee_id', $employee->id)
                ->where('status', 'Approved')
                ->where(function($query) use ($date) {
                    $query->whereDate('leave_date', $date)
                        ->orWhere(function($q) use ($date) {
                            $q->whereDate('leave_from', '<=', $date)
                                ->whereDate('leave_to', '>=', $date);
                        });
                })
                ->exists();
                
            if ($hasLeave) {
                continue;
            }
            
            // Create no pay record
           try{
                $record = NoPayRecord::firstOrCreate([
                    'employee_id' => $employee->id,
                    'date' => $date,
                ], [
                    'no_pay_count' => 1, // Default full day
                    'description' => 'Automatic no-pay record - no attendance and no approved leave',
                    'status' => 'Pending',
                    'processed_by' => auth::id(),
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to create no-pay record: ' . $e->getMessage()], 500);
           }
            
            if ($record->wasRecentlyCreated) {
                $generatedRecords[] = $record;
            }
        }
        
        return response()->json([
            'message' => count($generatedRecords) . ' no-pay records generated',
            'records' => $generatedRecords
        ]);
    }
    
    public function getNoPayStats(Request $request)
    {
        $query = NoPayRecord::query();
        
        if ($request->has('month')) {
            $query->whereMonth('date', $request->month);
        }
        
        if ($request->has('year')) {
            $query->whereYear('date', $request->year);
        }
        
        $totalRecords = $query->count();
        $totalDays = $query->sum('no_pay_count');
        $affectedEmployees = $query->distinct('employee_id')->count('employee_id');
        
        return response()->json([
            'total_records' => $totalRecords,
            'total_days' => $totalDays,
            'affected_employees' => $affectedEmployees
        ]);
    }
}