<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    /**
     * Store the Push Subscription.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required'
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $user = auth()->user();

        if ($user) {
            $user->updatePushSubscription($endpoint, $key, $token);
            \Log::info("Push subscribed for user: " . $user->name);
            return response()->json(['success' => true]);
        }

        \Log::error("Push subscription failed: No authenticated user found.");
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
