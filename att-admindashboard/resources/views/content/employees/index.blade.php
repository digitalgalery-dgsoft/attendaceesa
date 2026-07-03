@extends('layouts/layoutMaster')

@section('title', 'Manage Employees')

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Management /</span> Employees
</h4>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Employee List</h5>
    <!-- Create button could go here -->
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>EMP No</th>
          <th>Name</th>
          <th>Email</th>
          <th>Branch</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach($employees as $employee)
        <tr>
          <td><strong>{{ $employee->employee_no }}</strong></td>
          <td>
            <div class="d-flex justify-content-start align-items-center">
              <div class="avatar avatar-sm me-2">
                <span class="avatar-initial rounded-circle bg-label-success">{{ substr($employee->full_name, 0, 2) }}</span>
              </div>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ $employee->full_name }}</span>
                <small class="text-muted">{{ $employee->gender }}</small>
              </div>
            </div>
          </td>
          <td>{{ $employee->email }}</td>
          <td>{{ $employee->branch->name ?? '-' }}</td>
          <td>
            <span class="badge bg-label-info">{{ ucfirst($employee->employment_status) }}</span>
          </td>
          <td>
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="javascript:void(0);"><i class="ti ti-pencil me-1"></i> Edit</a>
                <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure?')"><i class="ti ti-trash me-1"></i> Delete</button>
                </form>
              </div>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
