<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\ApiDataController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\TimeCardController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\AllowancesController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\ResignationController;
use App\Http\Controllers\LeaveCalenderController;
use App\Http\Controllers\SubDepartmentsController;
use App\Http\Controllers\LeaveMasterController;
use App\Http\Controllers\NoPayController;
use App\Http\Controllers\SalaryProcessController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->get('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->noContent();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/test', [AuthController::class, 'test']);

Route::apiResource('users', UserController::class);
Route::apiResource('shifts', ShiftController::class);
Route::apiResource('employees', EmployeeController::class);
Route::get('/emp/table', [EmployeeController::class, 'getEmployeesForTable']);
Route::get('/emp/search', [EmployeeController::class, 'search']);
Route::apiResource('loans', LoanController::class);
Route::apiResource('allowances', AllowancesController::class);
Route::get('/allowance/by-company-or-department', [AllowancesController::class, 'getAllowancesByCompanyOrDepartment']);

Route::apiResource('deductions', DeductionController::class);
Route::apiResource('leave-calendars', LeaveCalenderController::class);
Route::apiResource('companies', CompanyController::class);
Route::apiResource('departments', DepartmentsController::class)->only(['store', 'update', 'destroy']);
Route::apiResource('subdepartments', SubDepartmentsController::class);
Route::apiResource('rosters', RosterController::class);
Route::apiResource('overtime', OvertimeController::class);
Route::apiResource('leave-masters', LeaveMasterController::class);
Route::apiResource('salary-process', SalaryProcessController::class);
Route::get('/Leave-Master/{employeeId}/counts', [LeaveMasterController::class, 'getLeaveRecordCountsByEmployee']);

Route::get('/Leave-Master/status/pending', [LeaveMasterController::class, 'getPendingLeaveRecords']);
Route::get('/Leave-Master/status/approved', [LeaveMasterController::class, 'getApprovedLeaveRecords']);
Route::get('/Leave-Master/status/hr-approved', [LeaveMasterController::class, 'getHRApprovedLeaveRecords']);


Route::get('/time-cards', [TimeCardController::class, 'index']);


Route::prefix('apiData')->group(function () {
    Route::get('/companies', [ApiDataController::class, 'companies']);
    Route::get('/departments', [ApiDataController::class, 'departments']);
    Route::get('/subDepartments', [ApiDataController::class, 'subDepartments']);
    Route::get('/designations', [ApiDataController::class, 'designations']);

    Route::get('/companies/{id}', [ApiDataController::class, 'companiesById']);
    Route::get('/departments/{id}', [ApiDataController::class, 'departmentsById']);
    Route::get('/subDepartments/{id}', [ApiDataController::class, 'subDepartmentsById']);
    Route::get('/subDepartments/{id}/employees', [ApiDataController::class, 'employeesBySubDepartment']);
});

// Resignation routes
Route::get('/resignations', [ResignationController::class, 'index']);
Route::post('/resignations', [ResignationController::class, 'store']);
Route::get('/resignations/{id}', [ResignationController::class, 'show']);
Route::put('/resignations/{id}/status', [ResignationController::class, 'updateStatus']);

// Document routes
Route::post('/resignations/{id}/documents', [ResignationController::class, 'uploadDocuments']);
Route::delete('/resignations/{resignationId}/documents/{documentId}', [ResignationController::class, 'destroyDocument']);

//time card
Route::put('/time-cards/{id}', [TimeCardController::class, 'update']);
Route::delete('/time-cards/{id}', [TimeCardController::class, 'destroy']);
Route::get('/employees/by-nic/{nic}', [EmployeeController::class, 'getByNic']);
Route::post('/time-cards', [TimeCardController::class, 'store']);
Route::post('/attendance', [TimeCardController::class, 'attendance']);
// Route::post('/attendance/mark-absentees', [TimeCardController::class, 'markAbsentees']);
Route::get('/time-cards/search-employee', [TimeCardController::class, 'searchByEmployee']);
Route::post('/attendance/import-excel', [TimeCardController::class, 'importExcel']);
Route::get('/companies', [CompanyController::class, 'index']);

//get employees by month and company
Route::get('/salary/process/employees-by-month', [SalaryProcessController::class, 'getEmployeesByMonthAndCompany']);
Route::post('/salary/process/allowances', [SalaryProcessController::class, 'updateEmployeesAllowances']);

Route::post('/attendance/mark-absentees', [TimeCardController::class, 'markAbsentees']);
Route::get('/absentees', [ApiDataController::class, 'Absentees']);
// No Pay routes
Route::get('no-pay-records', [NoPayController::class, 'index']);
Route::post('no-pay-records', [NoPayController::class, 'store']);
Route::put('no-pay-records/{id}', [NoPayController::class, 'update']);
Route::post('no-pay-records/bulk-update', [NoPayController::class, 'bulkUpdateStatus']);
Route::delete('no-pay-records/{id}', [NoPayController::class, 'destroy']);
Route::delete('no-pay-records/bulk-delete', [NoPayController::class, 'bulkDestroy']);
Route::post('no-pay-records/generate', [NoPayController::class, 'generateDailyNoPayRecords']);
Route::get('no-pay-records/stats', [NoPayController::class, 'getNoPayStats']);

// Allowances import/export routes
Route::get('/allowances/template/download', [AllowancesController::class, 'downloadTemplate']);
Route::post('/allowances/import', [AllowancesController::class, 'import']);
Route::get('/rosters/search', [RosterController::class, 'search']);
