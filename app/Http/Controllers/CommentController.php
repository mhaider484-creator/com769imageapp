<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Post $post) {
        return response()->json(
            $post->comments()->with('user')->latest()->get()
        );
    }

    public function store(Request $request, Post $post) {
        $data = $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'text' => $data['text'],
        ]);

        return response()->json($comment->load('user'), 201);
    }
}
