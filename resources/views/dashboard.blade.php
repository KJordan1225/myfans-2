@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    {{-- LEFT NAV SIDEBAR --}}
    @include('layouts.components.sidebar')

    {{-- MAIN AREA (header row spans this area; right panel sits under it) --}}
    <div class="col-md-9">
        {{-- HEADER ROW: Title (left) + Logout (right) --}}
        <div class="d-flex justify-content-between align-items-center my-3">
            <h3 class="mb-0">Dashboard</h3>
            <div>
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
        <hr class="mt-0" />

        {{-- CONTENT ROW: MAIN CONTENT (left) + RIGHT SIDEBAR PANEL (right) --}}
        <div class="row g-4">
            {{-- MAIN CONTENT --}}
            <div class="col-lg-8">
                {{-- Flash messages --}}
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="py-12">
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div class="dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900 dark:text-gray-100">
                                {{ __("You're logged in!") }}
                            </div>

                            <div class="p-4">
                                <p>
                                    <strong>STEP 1:</strong> Update your User Profile.<br>
                                    Click the <em>Profile-Create/Update Your Profile</em> link in the left sidebar
                                    navigation menu. Complete your profile details and save.<br>
                                    <strong>IMPORTANT:</strong> Make sure to set your Display Name to the EXACT same
                                    UserName you used to register your account. This is how your subscribers will find you.
                                </p>
                                <br>
                                <p>
                                    <strong>STEP 2:</strong> If you are a content creator, check the
                                    <em>"I'm a content creator"</em> box at the bottom of the Profile page, then pay the
                                    once-per-lifetime $5 processing fee to become a content creator. You’ll then be able
                                    to create your subscription plan and start posting content.
                                </p>
                                <p>
                                    To become a verified creator, pay a one-time $5 processing fee. This helps confirm
                                    authenticity and maintain a secure, high-quality environment. Once paid, you get
                                    lifetime access to creator tools and monetization—no recurring charges.
                                </p>
                                <br>
                                <p>
                                    <strong>STEP 3: Payment Gateway Onboarding</strong><br>
                                    Click the <em>Creator Account Dashboard</em> link in the left sidebar to create your
                                    Stripe account and link your bank for payouts. Click
                                    <strong>[Start/Continue Onboarding]</strong> to go to Stripe and follow the steps.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT SIDEBAR PANEL (sticks under header) --}}
            <div class="col-lg-4">
                <aside class="position-sticky" style="top: 1rem;">
                    {{-- Quick Actions --}}
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-semibold">Quick Actions</div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="#" class="btn btn-primary btn-sm">Edit Profile</a>
                                <a href="#" class="btn btn-outline-primary btn-sm">Create Plan</a>
                                <a href="#" class="btn btn-outline-secondary btn-sm">Stripe Onboarding</a>
                            </div>
                        </div>
                    </div>

                    {{-- Account Status --}}
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-semibold">Account Status</div>
                        <div class="card-body small">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <span class="text-muted">Profile:</span>
                                    <span class="ms-1 badge bg-success">Complete</span>
                                </li>
                                <li class="mb-2">
                                    <span class="text-muted">Creator:</span>
                                    {{-- Example condition; adjust to your data --}}
                                    @if(auth()->user()?->profile?->is_creator)
                                        <span class="ms-1 badge bg-success">Enabled</span>
                                    @else
                                        <span class="ms-1 badge bg-secondary">Not Enabled</span>
                                    @endif
                                </li>
                                <li class="mb-2">
                                    <span class="text-muted">Stripe:</span>
                                    @if(auth()->user()?->profile?->charges_enabled)
                                        <span class="ms-1 badge bg-success">Charges Enabled</span>
                                    @else
                                        <span class="ms-1 badge bg-warning text-dark">Action Needed</span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Help / Links --}}
                    <div class="card shadow-sm">
                        <div class="card-header fw-semibold">Help & Resources</div>
                        <div class="card-body small">
                            <ul class="mb-0">
                                <li><a href="#">Getting Started</a></li>
                                <li><a href="#">Contact Support</a></li>
                                <li><a href="#">Terms & Conditions</a></li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div> {{-- /row --}}
    </div> {{-- /col-md-9 --}}
</div> {{-- /row --}}
@endsection
