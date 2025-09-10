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
					<br />
					<p>
						STEP 2: If you are a content creator, make sure to check the "I'm a content creator"
								box at the bottom of the Profile-Create/Update Your Profile page.
								Then pay the once per lifetime $5 processing fee to become a content creator.
								You will then be able to create your subscription plan and start posting content.<br /><br />
								<p>To become a verified creator on our platform, users must pay a one-time $5 processing fee. 
									This small investment helps us confirm the authenticity of each creator and maintain a 
									secure, high-quality environment for both creators and subscribers. Once paid, this fee 
									grants you lifetime access to exclusive creator tools, features, and monetization 
									options—no recurring charges or hidden costs. It’s a simple, affordable way to unlock your 
									earning potential and join our growing community of content creators. By completing this 
									step, you ensure your profile stands out as legitimate and trusted, ready to start building 
									and monetizing your audience. 
								</p>
								<br />
								<p>
									STEP 3: Payment Gateway Onboarding<br />
									Click the Creator Account Dashboard link in the left sidebar
									navigation menu to create account with the Stripe Payment gateway 
									and link your bank account for payment drops.
									Click the [Start/Continue Onboarding] which will redirect you to 
									the Stripe Account Onboarding site. Follow the instructions to create
									an account and link your bank account in order to receive payment deposits.<br /><br />
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

