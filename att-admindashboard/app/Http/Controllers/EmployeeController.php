<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['company', 'branch', 'user'])->get();
        return view('content.employees.index', compact('employees'));
    }

    public function create()
    {
        $companies = Company::all();
        $branches = Branch::all();
        return view('content.employees.create', compact('companies', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string|max:80',
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'gender' => 'required|in:male,female',
            'employment_status' => 'required|in:permanent,contract,probation,intern,resigned',
        ]);

        // Create User for login
        $user = User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make('password123'), // Default password
        ]);

        // Create Employee
        $employeeData = $validated;
        $employeeData['user_id'] = $user->id;
        
        Employee::create($employeeData);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee)
    {
        $companies = Company::all();
        $branches = Branch::all();
        return view('content.employees.edit', compact('employee', 'companies', 'branches'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string|max:80',
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'gender' => 'required|in:male,female',
            'employment_status' => 'required|in:permanent,contract,probation,intern,resigned',
        ]);

        $employee->user->update([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
        ]);

        $employee->update($validated);
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        if($employee->user) {
            $employee->user->delete();
        }
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
