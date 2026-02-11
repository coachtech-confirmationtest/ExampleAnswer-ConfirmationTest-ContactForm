<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Response;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::all();

        return TagResource::collection($tags);
    }

    public function store(StoreTagRequest $request)
    {
        $tag = Tag::create($request->validated());

        return response()->json(null, Response::HTTP_CREATED);
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
