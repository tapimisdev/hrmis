<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class NotesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // List notes for authenticated user
    public function index()
    {
        $notes = Auth::user()->notes()->select('id', 'title', 'hasPin', 'created_at')->get();
        return response()->json($notes);
    }

    // Create a new note
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'hasPin' => 'boolean',
            'pin' => 'nullable|string|size:4|regex:/^\d{4}$/', // 4-digit numeric string
        ]);

        $data = $request->only(['title', 'content', 'hasPin']);
        if ($request->hasPin && $request->pin) {
            $data['pin'] = Hash::make($request->pin);
        }

        $note = Auth::user()->notes()->create($data);
        return response()->json($note, 201);
    }

    // Show a note (requires PIN if hasPin)
    public function show(Note $note, Request $request)
    {
        $this->authorize('view', $note);

        if ($note->hasPin) {
            $request->validate(['pin' => 'required|string|size:4|regex:/^\d{4}$/']);
            if (!$note->verifyPin($request->pin)) {
                return response()->json(['error' => 'Invalid PIN'], 403);
            }
        }

        return response()->json($note);
    }

    // Update a note (requires PIN if hasPin)
    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        if ($note->hasPin) {
            $request->validate(['pin' => 'required|string|size:4|regex:/^\d{4}$/']);
            if (!$note->verifyPin($request->pin)) {
                return response()->json(['error' => 'Invalid PIN'], 403);
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'hasPin' => 'boolean',
            'pin' => 'nullable|string|size:4|regex:/^\d{4}$/',
        ]);

        $data = $request->only(['title', 'content', 'hasPin']);
        if ($request->hasPin && $request->pin) {
            $data['pin'] = Hash::make($request->pin);
        } else {
            $data['pin'] = null; // Clear PIN if not provided
        }

        $note->update($data);
        return response()->json($note);
    }

    // Delete a note (requires PIN if hasPin)
    public function destroy(Note $note, Request $request)
    {
        $this->authorize('delete', $note);

        if ($note->hasPin) {
            $request->validate(['pin' => 'required|string|size:4|regex:/^\d{4}$/']);
            if (!$note->verifyPin($request->pin)) {
                return response()->json(['error' => 'Invalid PIN'], 403);
            }
        }

        $note->delete();
        return response()->json(['message' => 'Note deleted']);
    }
}