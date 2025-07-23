<?php

use App\Http\Controllers\ApiDataController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\AllowancesController;
use App\Http\Controllers\ResignationController;
use App\Http\Controllers\LeaveCalenderController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\SubDepartmentsController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\TimeCardController;

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
Route::apiResource('deductions', DeductionController::class);
Route::apiResource('leave-calendars', LeaveCalenderController::class);
Route::apiResource('companies', CompanyController::class);
Route::apiResource('departments', DepartmentsController::class)->only(['store', 'update', 'destroy']);
Route::apiResource('subdepartments', SubDepartmentsController::class);
Route::apiResource('rosters', RosterController::class);
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
Route::get('/employees/by-nic/{nic}', [EmployeeController::class, 'getByNic']);
Route::post('/time-cards', [TimeCardController::class, 'store']);
Route::post('/attendance', [TimeCardController::class, 'attendance']);