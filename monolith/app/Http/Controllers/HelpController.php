<?php

namespace App\Http\Controllers;

use App\Models\HelpSession;
use App\Models\HelpMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $session = HelpSession::where('user_id', $user->id)->latest()->first();
        return view('help.chat', ['session' => $session]);
    }

    public function start(Request $request)
    {
        $user = Auth::user();
        $session = HelpSession::create(['user_id' => $user->id, 'status' => 'open']);
        return redirect()->route('help.show', $session->id);
    }

    public function show($id)
    {
        $session = HelpSession::findOrFail($id);
        $this->authorizeUser($session);
        return view('help.chat', ['session' => $session]);
    }

    public function messages($id, Request $request)
    {
        $session = HelpSession::findOrFail($id);
        $this->authorizeUser($session);
        
        $query = $session->messages()->with('user');
        
        if ($request->has('last_id')) {
            $query->where('id', '>', $request->last_id);
        }
        
        return response()->json($query->get());
    }

    // Return active open session for current user with recent messages (for popup)
    public function active(Request $request)
    {
        $user = Auth::user();
        $session = HelpSession::where('user_id', $user->id)->where('status', 'open')->latest()->first();
        if (! $session) {
            return response()->json(['active' => false]);
        }

        $query = $session->messages()->with('user');
        if ($request->has('last_id')) {
            $query->where('id', '>', $request->last_id);
        }

        $messages = $query->get();
        return response()->json(['active' => true, 'session' => $session, 'messages' => $messages]);
    }

    // Send message via popup endpoint (user)
    public function popupSend(Request $request)
    {
        $request->validate([
            'help_session_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $session = HelpSession::findOrFail($request->help_session_id);
        // ensure session belongs to user and is open
        $this->authorizeUser($session);
        if ($session->status === 'closed') {
            return response()->json(['error' => 'Session closed'], 422);
        }

        $msg = $session->addMessage(Auth::id(), $request->message);

        return response()->json($msg, 201);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'help_session_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $session = HelpSession::findOrFail($request->help_session_id);
        $this->authorizeUser($session);

        if ($session->status === 'closed') {
            return response()->json(['error' => 'Session closed'], 422);
        }

        $msg = $session->addMessage(Auth::id(), $request->message);

        return response()->json($msg, 201);
    }

    protected function authorizeUser(HelpSession $session)
    {
        $user = Auth::user();
        if ($user->id !== $session->user_id && !$user->isAdmin()) {
            abort(403);
        }
    }
}
