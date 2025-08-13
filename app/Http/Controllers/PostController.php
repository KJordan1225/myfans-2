<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Post\StorePostRequest;

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
            'price' => $request->price,
            'is_paid' => $request->boolean('is_paid'),
            'visibility' => $request->visibility,
        ]);

        return redirect()->route('creator.posts.create')
                     ->with('success', 'Post created! Now upload media.');
    } 

    public function authUserPostsEdit(string $id)
    {
        $post = Post::find($id);

        return view ('post.authUserEdit', compact('post'));
    }
}
