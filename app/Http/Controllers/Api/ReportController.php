<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason'         => ['required', 'in:'.implode(',', Report::REASONS)],
            // 'other' seçiləndə izah olmadan şikayət admin üçün faydasızdır.
            'details'        => ['nullable', 'required_if:reason,other', 'string', 'max:2000'],
            'ride_id'        => ['nullable', 'integer', 'exists:rides,id'],
            'booking_id'     => ['nullable', 'integer', 'exists:bookings,id'],
        ]);

        abort_if(
            $data['target_user_id'] === $request->user()->id,
            422,
            'Özünü şikayət edə bilməzsən.'
        );

        $report = Report::create($data + [
            'reporter_id' => $request->user()->id,
            'status'      => 'open',
        ]);

        return response()->json(['data' => $report], 201);
    }
}
