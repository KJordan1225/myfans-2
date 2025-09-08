@extends('layouts.app')

@section('title')
    Dashboard
@endsection

@section('content')
    <div class="row">
    <!-- sidebar here -->
    @include('layouts.components.sidebar')

    <div class="col-md-9">
        {{-- Row with Dashboard title on left, Logout on right --}}
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
        <hr />

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        {{ __("You're logged in!") }}
                    </div>
					<p>
						STEP 1: Update your User Profile.<br />
								Click the Profile-Create/Update Your Profile link in the left sidebar
								navigation menu. Complete your profile details and save.<br />
								IMPORTANT: Make sure to set your Display Name to the EXACT same UserName 
								you used to register your account. This is how your subscribers will find you.
					</p>
                </div>
            </div>
        </div>
    </div>
</div>


				</div>
			</div>
		</div>
	</div>
@endsection

