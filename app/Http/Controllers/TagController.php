<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;

class TagController extends Controller
{
    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function store(StoreTagRequest $request)
    {
        Tag::create($request->validated());

        return redirect('/admin');
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return redirect('/admin');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect('/admin');
    }
}
