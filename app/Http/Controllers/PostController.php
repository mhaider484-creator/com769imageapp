<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\AzureBlobService;

class PostController extends Controller
{
    public function index(Request $request , AzureBlobService $azure)
    {
        $q = $request->query('q');
        $userId = $request->user()->id;

        $posts = Post::with(['user', 'comments.user', 'likes'])
            ->when($q, function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('caption', 'like', "%{$q}%");
            })
            ->latest()
            ->get()
            ->map(function ($post) use ($userId,$azure) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'caption' => $post->caption,
                    'location' => $post->location,
                    'people' => $post->people
                        ? array_map('trim', explode(',', $post->people))
                        : [],
                    'image_url' => $azure->url($post->image_path),
                    // 'image_path'  => $post->image_url ?? ( $post->image_path ? url('storage/'.$post->image_path) : null ),
                    'created_at' => $post->created_at,
                    'user_id' => $post->user->id,
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                    ],
                    // ✅ COUNTS
                    'likes_count' => $post->likes->count(),
                    'comments_count' => $post->comments->count(),
                    // ✅ HAS CURRENT USER LIKED?
                    'liked' => $post->likes->contains('user_id', $userId),
                ];
            });

        return response()->json($posts);
    }


    public function store(Request $request , AzureBlobService $azure) {
        $user = $request->user();
        if ($user->role !== 'creator') {
            return response()->json(['message'=>'Only creators can upload.'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'caption' => 'nullable|string',
            'location' => 'nullable|string|max:150',
            'people' => 'nullable|string|max:255',
            'image' => 'required|image|max:5120',
        ]);

        // $path = $request->file('image')->store('posts', 'public');
        $path = $azure->upload($request->file('image'), 'posts');
        $post = Post::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'caption' => $data['caption'] ?? null,
            'location' => $data['location'] ?? null,
            'people' => $data['people'] ?? null,
            'image_path' => $path,
        ]);

        return response()->json($post->load(['user','comments.user','likes']), 201);
    }

    public function show(Post $post) {
        return response()->json($post->load(['user','comments.user','likes']));
    }

    public function update(Request $request, Post $post) {
        $user = $request->user();
        if ($user->role !== 'creator' || $post->user_id !== $user->id) {
            return response()->json(['message'=>'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'caption' => 'nullable|string',
            'location' => 'nullable|string|max:150',
            'people' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            if ($post->image_path) Storage::disk('public')->delete($post->image_path);
            $post->image_path = $request->file('image')->store('posts', 'public');
        }

        $post->title = $data['title'];
        $post->caption = $data['caption'] ?? null;
        $post->location = $data['location'] ?? null;
        $post->people = $data['people'] ?? null;
        $post->save();

        return response()->json($post->load(['user','comments.user','likes']));
    }

    public function destroy(Request $request, Post $post) {
        $user = $request->user();
        if ($user->role !== 'creator' || $post->user_id !== $user->id) {
            return response()->json(['message'=>'Forbidden'], 403);
        }

        if ($post->image_path) Storage::disk('public')->delete($post->image_path);
        $post->delete();

        return response()->json(['message'=>'Deleted']);
    }

    public function toggleLike(Request $request, Post $post) {
        $user = $request->user();

        $like = Like::where('post_id',$post->id)->where('user_id',$user->id)->first();
        if ($like) {
            $like->delete();
            return response()->json(['liked'=>false]);
        }

        Like::create(['post_id'=>$post->id,'user_id'=>$user->id]);
        return response()->json(['liked'=>true]);
    }
}
