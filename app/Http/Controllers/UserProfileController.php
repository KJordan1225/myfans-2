<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\SubscriptionService;

class UserProfileController extends Controller
{
	
    public function __construct(
        protected SubscriptionService $service
    ) {}
    
    /**
	 * User Profile functions.
	 * Accessible by users w/creator privileges
	 */


    /**
     * Display a listing of the profile of authenticated user.
     */
    public function index()
    {
        $user = Auth::user();

        $profile = $user->profile; // assumes hasOne('UserProfile') relationship

        if ($profile) {
            return redirect()->route('user-profile.edit', $profile);
        } else {
            return redirect()->route('user-profile.create');
        }
    }

    /**
     * Show the form for creating a new user profile.
     */
    public function create()
    {
        return view('user-profile.create');
    }

    /**
     * Store a newly created user profile in database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'bio'          => 'nullable|string',
            'avatar'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'website'      => 'nullable|url|max:255',
            'twitter'      => 'nullable|string|max:255',
            'instagram'    => 'nullable|string|max:255',
            'is_creator'   => 'sometimes|boolean',
        ]);

        $user = Auth::user();

        // Prevent duplicate profiles
        if ($user->userProfile) {
            return redirect()->route('user-profiles.edit', $user->userProfile)
                            ->with('warning', 'You already have a profile.');
        }

            if ($request->hasFile('avatar')) {
                $profile->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }

            if ($request->hasFile('banner')) {
                $profile->addMediaFromRequest('banner')->toMediaCollection('banner');
            }


        $profile = UserProfile::create([
            'user_id'      => $user->id,
            'display_name' => $request->input('display_name'),
            'bio'          => $request->input('bio'),
            'website'      => $request->input('website'),
            'twitter'      => $request->input('twitter'),
            'instagram'    => $request->input('instagram'),
            'is_creator'   => $request->has('is_creator'),
            'stripe_id'    => null,
            'balance'      => 0,
        ]);          

        return redirect()->route('dashboard')
			->with('success', 'Profile created successfully!');
        
    }

    /**
     * Display the specified user profile record.
     */
    public function show(string $id)
    {
        
    }

    /**
     * Show the form for editing the specified user profile.
     */
    public function edit(UserProfile $profile)
    {
        $user = Auth::user();

        $profile = $user->profile;
        
        if (!$profile) {
            return redirect()->route('dashboard')->with('error', 'Profile not found.');
        }

        return view('user-profile.edit', compact('profile'));
    }

    /**
     * Update the specified user profile in the database.
     */
    public function update(Request $request, string $id)
    {
        $userProfile = UserProfile::find($id);

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'bio'          => 'nullable|string',
            'avatar'       => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'       => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',
            'website'      => 'nullable|url|max:255',
            'twitter'      => 'nullable|string|max:255',
            'instagram'    => 'nullable|string|max:255',
            'is_creator'   => 'sometimes|boolean',
        ]);


        if ($request->hasFile('avatar')) {
            $userProfile->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        if ($request->hasFile('banner')) {
            $userProfile->addMediaFromRequest('banner')->toMediaCollection('banner');
        }


        // Handle checkbox for is_creator
        $validated['is_creator'] = $request->has('is_creator');

        $userProfile->update($validated);

        // check if processing fee has been paid
        if ($userProfile->is_creator && !$userProfile->processing_paid){
            return redirect()
            ->route('creator.stripe.checkout');
        }

        return redirect()
            ->route('user-profile.edit', $userProfile)
            ->with('success', 'Profile updated successfully.'); 
    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Show a public user profile based on their @username.
     */
    public function showByUsername(string $username)
    {
        // Strip the '@' if it exists
        $username = ltrim($username, '@');
        
        // Find the user by username or return 404
        // $user = User::where('username', $username)->firstOrFail();

        // Load the related UserProfile (assuming hasOne relationship)
        $profile = UserProfile::where('display_name', $username)->firstOrFail();
        $user = $profile->user;
        
        // Optionally load other data: posts, subscriptions, etc.
        $posts = Post::where('user_id', $user->id)->get();
        $postCount = $posts->count();
        $subscription = $user->ownedSubscription;
        
        
        
        return view('profile.public', [
            'user' => $user,
            'profile' => $profile,
            'posts' => $posts,
            'postCount' => $postCount,
            'subscription' => $subscription,
        ]); 
    }

    public function showByUsernamePostDetail(Request $request, Post $post)
    {
        $subscriber = $request->user();
        $subscription = $post->user->ownedSubscription;        
        $isSubscribed = $this->service->isSubscribed($subscriber, $subscription);

        return view('profile.post-detail', compact('post', 'isSubscribed', 'subscription'));
    }

}    

