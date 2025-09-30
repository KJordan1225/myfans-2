<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{
    public function authUserPostsList()
    {
        $userId = Auth::id();
        $posts = Post::where('user_id', $userId)->get();

        return view('post.authUserList', compact('posts'));
    }

    public function authUserPostsCreate()
    {
        $userId = Auth::id();
        $posts = Post::where('user_id', $userId)->get();

        return view('post.authUserCreate', compact('posts', 'userId'));
    }

   /*************************
	 * Store input from create posts blade
	 * into database
	 **************************/
	
	public function authUserPostsStore(StorePostRequest $request)
    {
        $post = Post::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'body' => $request->body,
            'media_type' => $request->media_type,
            'price' => $request->price,
            'is_paid' => $request->boolean('is_paid'),
            'visibility' => $request->visibility,
        ]);

        if ($request->hasFile('video')) {
            $post->addMediaFromRequest('video')
                ->toMediaCollection('videos');
        }

        if ($request->hasFile('image')) {
            $post->addMediaFromRequest('image')
                ->toMediaCollection('images');
        }

        return redirect()->route('creator.posts.create')
                     ->with('success', 'Post created! Now upload media.');
    } 

    public function authUserPostsEdit(string $id)
    {
        $post = Post::find($id);

        return view ('post.authUserEdit', compact('post'));
    }

    public function authUserPostsUpdate(UpdatePostRequest $request, Post $post)
    {
        
        // Ensure the logged-in user owns this post
        if ($post->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Validate request
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'is_paid' => ['boolean'],
            'visibility' => ['required', 'in:public,subscribers,paid'],
        ]);

        // Checkbox handling — if unchecked, make sure it's false
        $validated['is_paid'] = $request->has('is_paid');

        if ($request->hasFile('video')) {
            // appends another video; if you want to replace, clear first:
            // $post->clearMediaCollection('videos');
            $post->addMediaFromRequest('video')->toMediaCollection('videos');
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $post->addMedia($image)->toMediaCollection('images');
            }
        }


        // Update post
        $post->update($validated);        

        // Redirect back with success message
        return redirect()
            ->route('creator.posts.edit', $post)
            ->with('success', 'Post updated successfully.');
    }

    public function authUserPostsDelete(Post $post)
    {        
        $post->delete();

        // Store a flash message for SweetAlert success toast
        return redirect()->route('creator.posts.list')
            ->with('success', 'Post deleted successfully.');
    }

    public function showPosts(string $username)
    {
        /** @var \App\Models\User $creator */
        $creator = User::query()
            ->where('name', $username)            
            ->first();

        $sub = Subscription::where('user_id', Auth::id())
            ->where('creator_id', $creator->id)
            ->first(); 

        // Eager-load media to prevent N+1 when calling getFirstMediaUrl()/getMedia()
        $posts = Post::query()
            ->with('media')
            ->where('user_id', $creator->id)
            ->latest()
            ->paginate(5); // 10 per page; tweak as needed 

        $subscribed = $this->subscribed_to_creator($creator);

        return view('posts.show', compact('creator', 'posts', 'sub', 'subscribed'));
    }

    /**
     * Determine if the (authenticated) subscriber is subscribed to the given creator.
     *
     * @param  \App\Models\User  $creator  The creator user (owner of the plan/content)
     * @param  \App\Models\User|null $viewer Optional override for the subscriber (defaults to auth user)
     * @return bool
     */
    public function subscribed_to_creator(User $creator, ?User $viewer = null): bool
    {
        $viewer = $viewer ?: Auth::user();
        if (!$viewer) {
            return false; // not logged in
        }

        // Avoid creator subscribing to self (optional)
        if ($viewer->id === $creator->id) {
            return false;
        }

        // Cache briefly to minimize repeated DB queries per request bursts
        $cacheKey = sprintf('subscribed:%d:%d', $viewer->id, $creator->id);

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($viewer, $creator) {

            // Define which statuses count as "active access" in your app
            $activeStatuses = ['active', 'trialing', 'past_due']; 
            // If you only want strictly active, use ['active'].

            return Subscription::query()
                ->where('user_id', $viewer->id)     // subscriber
                ->where('creator_id', $creator->id) // creator
                ->whereIn('status', $activeStatuses)
                // treat as active while current period is not ended
                ->where(function ($q) {
                    $q->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
                })
                // if you support "cancel at period end", it still grants access until period end
                // ->where(function ($q) { $q->whereNull('cancel_at_period_end')->orWhere('cancel_at_period_end', false); })
                ->exists();
        });
    }

    
}
