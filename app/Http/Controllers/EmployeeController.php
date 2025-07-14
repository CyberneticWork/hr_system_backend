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
            'profilePicture' => 'required|image|max:2048',

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
                'relationshipType' => 'required|string|max:20',
                'spouseName' => 'required|string|max:100',
                'spouseAge' => 'required|numeric|min:18|max:100',
                'spouseDob' => 'required|date',
                'spouseNic' => 'required|string|max:20',
                // Note: profilePicture is handled separately in the file upload
            ]);

            // Validate address data
            $addressValidator = Validator::make($address, [
                'permanentAddress' => 'required|string|max:255',
                'temporaryAddress' => 'nullable|string|max:255',
                'email' => 'required|email',
                'alandLine' => 'nullable|string|max:20',
                'mobileLine' => 'nullable|string|max:20',
                'gnDivision' => 'nullable|string|max:100',
                'policeStation' => 'nullable|string|max:100',
                'district' => 'required|string|max:100',
                'province' => 'required|string|max:100',
                'electoralDivision' => 'nullable|string|max:100',

                // Emergency contact
                'emergencyContact.relationship' => 'required|string|max:50',
                'emergencyContact.contactName' => 'required|string|max:100',
                'emergencyContact.contactAddress' => 'required|string|max:255',
                'emergencyContact.contactTel' => 'required|string|max:20',
                //     // Add other address fields as needed
            ]);


            $compensationValidator = Validator::make($compensation, [
                'basicSalary' => 'required|numeric',
                'incrementValue' => 'nullable|numeric',
                'incrementEffectiveFrom' => 'nullable|date',
                'bankName' => 'nullable|string|max:100',
                'branchName' => 'nullable|string|max:100',
                'bankCode' => 'nullable|string|max:50',
                'branchCode' => 'nullable|string|max:50',
                'bankAccountNo' => 'nullable|string|max:50',
                'comments' => 'nullable|string|max:255',
                'secondaryEmp' => 'required|boolean',
                'primaryEmploymentBasic' => 'required|boolean',
                'enableEpfEtf' => 'required|boolean',
                'otActive' => 'required|boolean',
                'earlyDeduction' => 'required|boolean',
                'incrementActive' => 'required|boolean',
                'nopayActive' => 'required|boolean',
                'morningOt' => 'required|boolean',
                'eveningOt' => 'required|boolean',
                'budgetaryReliefAllowance2015' => 'required|boolean',
                'budgetaryReliefAllowance2016' => 'required|boolean',
            ]);


            $organizationValidator = Validator::make($organization, [
                'company' => 'required|string',
                'department' => 'required|string',
                'subDepartment' => 'required|string',
                'currentSupervisor' => 'nullable|string|max:100',
                'dateOfJoined' => 'required|date',
                'designation' => 'required|string|max:100',
                'probationPeriod' => 'required|boolean',
                'trainingPeriod' => 'required|boolean',
                'contractPeriod' => 'required|boolean',
                'probationFrom' => 'nullable|date',
                'probationTo' => 'nullable|date',
                'trainingFrom' => 'nullable|date',
                'trainingTo' => 'nullable|date',
                'contractFrom' => 'nullable|date',
                'contractTo' => 'nullable|date|after_or_equal:contractFrom',
                'confirmationDate' => 'nullable|date',
                'resignationDate' => 'nullable|date',
                'resignationLetter' => 'nullable',
                'resignationApproved' => 'required|boolean',
                'currentStatus' => 'required|string',
                'dayOff' => 'required|string'
            ]);


            // Add errors to main validator if any
            foreach ($personalValidator->errors()->toArray() as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("personal.$key", $message);
                }
            }

            foreach ($addressValidator->errors()->toArray() as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("address.$key", $message);
                }
            }

            foreach ($compensationValidator->errors()->toArray() as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("compensation.$key", $message);
                }
            }

            foreach ($organizationValidator->errors()->toArray() as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add("organization.$key", $message);
                }
            }

            // Add similar validation for compensation and organization
        });

        $profilePicturePath = null;
        if ($request->hasFile('profilePicture')) {
            $profilePicturePath = $request->file('profile_picture')->store('employee/profile_pictures', 'public');
            $personal['profile_picture_path'] = $profilePicturePath;
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json(['message' => 'Employee data validated and processed successfully.', 'personal' => $personal, 'address' => $address, 'compensation' => $compensation, 'organization' => $organization], 201);
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
