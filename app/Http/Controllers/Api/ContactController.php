<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $query = Contact::with(['category', 'tags']);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('gender') && $request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query->latest()->paginate(7);

        return ContactResource::collection($contacts);
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);

        if (! empty($tagIds)) {
            $contact->tags()->attach($tagIds);
        }

        return response()->json(null, Response::HTTP_CREATED);
    }

    public function show(Contact $contact)
    {
        return new ContactResource($contact->load(['category', 'tags']));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
