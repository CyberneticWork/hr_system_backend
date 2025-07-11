<?php

namespace App\Http\Controllers;

use App\Models\employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = employee::with(['employmentType', 'spouse', 'childrens', 'contactDetail', 'organizationAssignment'])->get();
        return response()->json($employees, 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Profile picture validation (from FormData)
            'profile_picture' => 'nullable|image|max:2048',

            // JSON fields validation (these come as JSON strings in FormData)
            'personal' => 'required|json',
            'address' => 'required|json',
            'compensation' => 'required|json',
            'organization' => 'required|json',
            'documents.*' => 'nullable|file|max:5120'
        ]);

        // Decode JSON data first
        $personal = json_decode($request->input('personal'), true);
        $address = json_decode($request->input('address'), true);
        $compensation = json_decode($request->input('compensation'), true);
        $organization = json_decode($request->input('organization'), true);

        // Then validate the decoded arrays
        $validator->after(function ($validator) use ($personal, $address, $compensation, $organization) {
            // Validate personal data
            $personalValidator = Validator::make($personal, [
                'title' => 'required|string|max:10',
                'attendanceEmpNo' => 'required|string|max:50',
                'epfNo' => 'required|string|max:50',
                'nicNumber' => 'required|string|max:20',
                'dob' => 'required|date',
                'gender' => 'required|in:Male,Female,Other',
                'religion' => 'nullable|string|max:50',
                'countryOfBirth' => 'nullable|string|max:100',
                'employmentStatus' => 'required',
                'nameWithInitial' => 'required|string|max:100',
                'fullName' => 'required|string|max:100',
                'displayName' => 'required|string|max:100',
                'maritalStatus' => 'required|in:Single,Married,Divorced,Widowed',
                'relationshipType' => 'nullable|string|max:20',
                'spouseName' => 'nullable|string|max:100',
                'spouseAge' => 'nullable|numeric|min:18|max:100',
                'spouseDob' => 'nullable|date',
                'spouseNic' => 'nullable|string|max:20',
                // Note: profilePicture is handled separately in the file upload
            ]);

            // Validate address data
            // $addressValidator = Validator::make($address, [
            //     'permanentAddress' => 'required|string|max:255',
            //     'temporaryAddress' => 'nullable|string|max:255',
            //     'email' => 'required|email|max:100',
            //     'landLine' => 'nullable|string|max:20',
            //     'mobileLine' => 'required|string|max:20',
            //     // Add other address fields as needed
            // ]);



            // Add errors to main validator if any
            foreach ($personalValidator->errors()->toArray() as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("personal.$key", $message);
                }
            }

            // foreach ($addressValidator->errors()->toArray() as $key => $messages) {
            //     foreach ($messages as $message) {
            //         $validator->errors()->add("address.$key", $message);
            //     }
            // }

            // Add similar validation for compensation and organization
        });

        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('employee/profile_pictures', 'public');
            $personal['profile_picture_path'] = $profilePicturePath;
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json(['message' => 'Employee data validated and processed successfully.', 'data' => $personal], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:10',
            'attendanceEmpNo' => 'required|string|max:50',
            'epfNo' => 'required|string|max:50',
            'nicNumber' => 'required|string|max:20',
            'dob' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'religion' => 'nullable|string|max:50',
            'countryOfBirth' => 'nullable|string|max:100',
            'employmentStatus' => 'required|in:permanentBasis,contractBasis,temporary,employeeActive',
            'nameWithInitial' => 'required|string|max:100',
            'fullName' => 'required|string|max:100',
            'displayName' => 'required|string|max:100',
            'maritalStatus' => 'required|in:Single,Married,Divorced,Widowed',
            'relationshipType' => 'nullable|string|max:20',
            'spouseName' => 'nullable|string|max:100',
            'spouseAge' => 'nullable|numeric|min:18|max:100',
            'spouseDob' => 'nullable|date',
            'spouseNic' => 'nullable|string|max:20',

            // Children array
            'children' => 'nullable|array',
            'children.*.name' => 'nullable|string|max:100',
            'children.*.age' => 'nullable|numeric|min:0|max:100',
            'children.*.dob' => 'nullable|date',
            'children.*.nic' => 'nullable|string|max:20',

            // Address object
            'address.permanentAddress' => 'required|string|max:255',
            'address.temporaryAddress' => 'nullable|string|max:255',
            'address.email' => 'required|email',
            'address.landLine' => 'nullable|string|max:20',
            'address.mobileLine' => 'nullable|string|max:20',
            'address.gnDivision' => 'nullable|string|max:100',
            'address.policeStation' => 'nullable|string|max:100',
            'address.district' => 'required|string|max:100',
            'address.province' => 'required|string|max:100',
            'address.electoralDivision' => 'nullable|string|max:100',

            // Emergency contact
            'address.emergencyContact.relationship' => 'required|string|max:50',
            'address.emergencyContact.contactName' => 'required|string|max:100',
            'address.emergencyContact.contactAddress' => 'required|string|max:255',
            'address.emergencyContact.contactTel' => 'required|string|max:20',

            // Compensation
            'compensation.basicSalary' => 'required|numeric',
            'compensation.incrementValue' => 'nullable|numeric',
            'compensation.incrementEffectiveFrom' => 'nullable|date',
            'compensation.bankName' => 'nullable|string|max:100',
            'compensation.branchName' => 'nullable|string|max:100',
            'compensation.bankCode' => 'nullable|string|max:50',
            'compensation.branchCode' => 'nullable|string|max:50',
            'compensation.bankAccountNo' => 'nullable|string|max:50',
            'compensation.comments' => 'nullable|string|max:255',
            'compensation.secondaryEmp' => 'required|boolean',
            'compensation.primaryEmploymentBasic' => 'required|boolean',
            'compensation.enableEpfEtf' => 'required|boolean',
            'compensation.otActive' => 'required|boolean',
            'compensation.earlyDeduction' => 'required|boolean',
            'compensation.incrementActive' => 'required|boolean',
            'compensation.nopayActive' => 'required|boolean',
            'compensation.morningOt' => 'required|boolean',
            'compensation.eveningOt' => 'required|boolean',
            'compensation.budgetaryReliefAllowance2015' => 'required|boolean',
            'compensation.budgetaryReliefAllowance2016' => 'required|boolean',

            // Organization Details
            'organizationDetails.company' => 'required|string',
            'organizationDetails.department' => 'required|string',
            'organizationDetails.subDepartment' => 'required|string',
            'organizationDetails.currentSupervisor' => 'nullable|string|max:100',
            'organizationDetails.dateOfJoined' => 'required|date',
            'organizationDetails.designation' => 'required|string|max:100',
            'organizationDetails.probationPeriod' => 'required|boolean',
            'organizationDetails.trainingPeriod' => 'required|boolean',
            'organizationDetails.contractPeriod' => 'required|boolean',
            'organizationDetails.probationFrom' => 'nullable|date',
            'organizationDetails.probationTo' => 'nullable|date',
            'organizationDetails.trainingFrom' => 'nullable|date',
            'organizationDetails.trainingTo' => 'nullable|date',
            'organizationDetails.contractFrom' => 'nullable|date',
            'organizationDetails.contractTo' => 'nullable|date|after_or_equal:organizationDetails.contractFrom',
            'organizationDetails.confirmationDate' => 'nullable|date',
            'organizationDetails.resignationDate' => 'nullable|date',
            'organizationDetails.resignationLetter' => 'nullable',
            'organizationDetails.resignationApproved' => 'required|boolean',
            'organizationDetails.currentStatus' => 'required|string',
            'organizationDetails.dayOff' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        return response()->json(['message' => 'Employee data validated and processed successfully.', 'data' => $request->all()], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
