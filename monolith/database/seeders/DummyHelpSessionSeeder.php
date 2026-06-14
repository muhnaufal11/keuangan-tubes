<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummyHelpSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = \App\Models\User::where('username', 'admin')->first();
        $user = \App\Models\User::where('id', '!=', $admin->id ?? 0)->first();
        
        if ($user) {
            // Check if session already exists for this user
            $session = \App\Models\HelpSession::where('user_id', $user->id)->where('status', 'open')->first();
            if (!$session) {
                $session = \App\Models\HelpSession::create([
                    'user_id' => $user->id,
                    'status' => 'open',
                ]);
            }

            // Check if message already exists
            $existingMessage = \App\Models\HelpMessage::where('help_session_id', $session->id)->first();
            if (!$existingMessage) {
                \App\Models\HelpMessage::create([
                    'help_session_id' => $session->id,
                    'user_id' => $user->id,
                    'message' => 'Hello admin, I need help with something.',
                ]);
            }
            
            echo "Help session ready for user {$user->username} (ID: {$user->id}) with session ID: {$session->id}\n";
        } else {
            echo "No regular user found to create test session\n";
        }
    }
}
