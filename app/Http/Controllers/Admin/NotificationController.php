<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index()
    {
        $dueNotes = Purchase::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', 'pending') // Adjust this based on your status column
            ->orderBy('due_date', 'asc')
            ->get();

        return view('admin.notifications', compact('dueNotes'));
    }

    public function count()
    {
        $count = Purchase::where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('status', 'pending')
            ->count();

        return response()->json(['count' => $count]);
    }
}