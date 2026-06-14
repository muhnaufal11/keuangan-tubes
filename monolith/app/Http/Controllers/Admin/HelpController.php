<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpSession;
use App\Models\HelpMessage;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index()
    {
        $sessions = HelpSession::with('user')->latest()->get();
        return view('admin.help.index', ['sessions' => $sessions]);
    }

    public function show($id)
    {
        $session = HelpSession::with('messages.user')->findOrFail($id);
        return view('admin.help.show', ['session' => $session]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'help_session_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $session = HelpSession::findOrFail($request->help_session_id);
        if ($session->status === 'closed') {
            return redirect()->back()->with('error', 'Session sudah ditutup, tidak dapat mengirim pesan.');
        }

        try {
            $msg = $session->addMessage($request->user()->id, $request->message);
            return response()->json($msg, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Admin can start or open a session for a specific user and be redirected to the admin chat
    public function startSession($userId)
    {
        $session = HelpSession::where('user_id', $userId)->latest()->first();
        if (! $session) {
            $session = HelpSession::create(['user_id' => $userId, 'status' => 'open']);
        }

        return redirect()->route('admin.help.show', $session->id);
    }

    // Return active open session (most recent) and messages for admin popup
    public function active()
    {
        $session = HelpSession::where('status', 'open')->latest()->first();
        if (! $session) {
            return response()->json(['active' => false]);
        }
        $messages = $session->messages()->with('user')->get();
        return response()->json(['active' => true, 'session' => $session, 'messages' => $messages]);
    }

    // Admin popup send message (AJAX)
    public function popupSend(Request $request)
    {
        $request->validate([
            'help_session_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        $session = HelpSession::findOrFail($request->help_session_id);
        if ($session->status === 'closed') {
            return response()->json(['error' => 'Session closed'], 422);
        }

        try {
            $msg = $session->addMessage($request->user()->id, $request->message);
            return response()->json($msg, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function close($id)
    {
        $session = HelpSession::findOrFail($id);
        $session->status = 'closed';
        $session->save();

        return redirect()->route('admin.help.index')->with('success', 'Help session #' . $session->id . ' telah ditutup.');
    }
}
