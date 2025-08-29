<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of the notifications for the authenticated user.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->get();
        return view('notifications', compact('notifications'));
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Notification $notification)
    {
        // Check if the notification belongs to the authenticated user
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this notification.');
        }
        
        $notification->delete();
        return redirect()->back()->with('flash.banner', 'Notification deleted successfully.')->with('flash.bannerStyle', 'success');
    }
}
