@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-person-circle fs-3 me-3 text-primary"></i>
            <div>
                <h4 class="mb-0 fw-bold">Mi perfil</h4>
                <small class="text-muted">{{ $user->name }} · {{ $user->email }}</small>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4 border-danger-subtle">
                    <div class="card-body">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        @include('profile.partials.mcp-connection-summary')
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        @include('profile.partials.api-token-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
