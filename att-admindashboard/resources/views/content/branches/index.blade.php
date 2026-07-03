@extends('layouts/layoutMaster')

@section('title', 'Manage Branches')

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Management /</span> Branches
</h4>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Branch List</h5>
    <!-- Create button could go here -->
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Company</th>
          <th>Code</th>
          <th>Branch Name</th>
          <th>Address</th>
          <th>Radius (m)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach($branches as $branch)
        <tr>
          <td>{{ $branch->id }}</td>
          <td>{{ $branch->company->name ?? '-' }}</td>
          <td><span class="badge bg-label-primary me-1">{{ $branch->code }}</span></td>
          <td><strong>{{ $branch->name }}</strong></td>
          <td>{{ Str::limit($branch->address, 30) }}</td>
          <td>{{ $branch->radius_meter }}</td>
          <td>
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="javascript:void(0);"><i class="ti ti-pencil me-1"></i> Edit</a>
                <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="d-inline">
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
