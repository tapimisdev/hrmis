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
            'title' => 'required|string|max:35',
            'content' => 'required|string',
            'hasPin' => 'boolean',
            'pin' => 'nullable|numeric|min:6',
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
            $request->validate(['pin' => 'required|numeric']);
            if (!$note->verifyPin($request->pin)) {
                return response()->json(['error' => 'Invalid PIN'], 403);
            }
        }

        return response()->json($note);
    }

    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'hasPin'  => 'boolean',
            'pin'     => 'nullable|numeric|min:6',
        ]);

        // Removed PIN requirement for unpinning or updating PIN
        // Note: This may reduce security, as unpinning no longer requires the current PIN

        $data = $request->only(['title', 'content']);

        // Handle pin logic explicitly
        if ($request->has('hasPin')) {
            $data['hasPin'] = $request->hasPin;

            if ($request->hasPin && $request->filled('pin')) {
                $data['pin'] = Hash::make($request->pin);
            }

            if (!$request->hasPin) {
                $data['pin'] = null;
            }
        }

        $note->update($data);

        return response()->json($note);
    }

    // Delete a note (requires PIN if hasPin)
    public function destroy(Note $note, Request $request)
    {
        $this->authorize('delete', $note);

        if ($note->hasPin) {
            $request->validate(['pin' => 'required']);
            if (!$note->verifyPin($request->pin)) {
                return response()->json(['error' => 'Invalid PIN'], 403);
            }
        }

        $note->delete();
        return response()->json(['message' => 'Note deleted']);
    }
}