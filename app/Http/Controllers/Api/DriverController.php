<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\VerificationDocument;
use App\Support\DriverVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function profile(Request $request)
    {
        $user    = $request->user();
        $profile = $user->driverProfile()->firstOrCreate([]);
        $profile->load(['vehicle', 'documents']);

        return response()->json([
            'data' => [
                'status'                  => $profile->status,
                'submitted_at'            => $profile->submitted_at?->toIso8601String(),
                'reviewed_at'             => $profile->reviewed_at?->toIso8601String(),
                'rejection_reason'        => $profile->rejection_reason,
                'instant_booking_default' => (bool) $profile->instant_booking_default,
                'vehicle'                 => $profile->vehicle
                    ? (new VehicleResource($profile->vehicle))->withPlate(true)
                    : null,
                'documents'               => $this->documentMap($profile),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'instant_booking_default' => ['required', 'boolean'],
        ]);

        $profile = $request->user()->driverProfile()->firstOrCreate([]);
        $profile->forceFill($data)->save();

        return response()->json(['data' => $profile]);
    }

    /**
     * Sənəd yükləmə. Hər növdən bir sətir olur (unique driver_profile_id+type),
     * ona görə təkrar yükləmə mövcud sətri əvəz edir və statusu yenidən
     * 'pending'-ə qaytarır.
     */
    public function uploadDocument(Request $request)
    {
        $data = $request->validate([
            'type'      => ['required', 'in:'.implode(',', VerificationDocument::TYPES)],
            'file'      => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'back_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        abort_if(
            $request->hasFile('back_file') && ! in_array($data['type'], VerificationDocument::TWO_SIDED, true),
            422,
            'Bu sənəd növünün arxa üzü olmur.'
        );

        $user    = $request->user();
        $profile = $user->driverProfile()->firstOrCreate([]);

        $document = DB::transaction(function () use ($request, $data, $profile) {
            $path     = $request->file('file')->store("documents/{$profile->id}", 'local');
            $backPath = $request->hasFile('back_file')
                ? $request->file('back_file')->store("documents/{$profile->id}", 'local')
                : null;

            $document = VerificationDocument::updateOrCreate(
                ['driver_profile_id' => $profile->id, 'type' => $data['type']],
                [
                    'status'           => 'pending',
                    'file_url'         => $path,
                    'back_file_url'    => $backPath,
                    'uploaded_at'      => now(),
                    'reviewed_at'      => null,
                    'rejection_reason' => null,
                ]
            );

            $this->refreshProfileStatus($profile);

            return $document;
        });

        return response()->json(['data' => $document], 201);
    }

    public function documents(Request $request)
    {
        $profile = $request->user()->driverProfile()->firstOrCreate([]);

        return response()->json(['data' => $this->documentMap($profile->load('documents'))]);
    }

    /** Dörd növün hamısı üçün sətir qaytarır — yüklənməyənlər 'not_uploaded'. */
    private function documentMap($profile): array
    {
        $existing = $profile->documents->keyBy('type');

        return collect(VerificationDocument::TYPES)->map(function ($type) use ($existing) {
            $doc = $existing->get($type);

            return [
                'type'             => $type,
                'status'           => $doc->status ?? 'not_uploaded',
                'uploaded_at'      => $doc?->uploaded_at?->toIso8601String(),
                'rejection_reason' => $doc->rejection_reason ?? null,
                'needs_back_side'  => in_array($type, VerificationDocument::TWO_SIDED, true),
            ];
        })->values()->all();
    }

    /**
     * Sürücünün ümumi statusu sənədlərdən törəyir. Eyni qayda admin panelində
     * də işlədilir — bax: App\Support\DriverVerification.
     */
    private function refreshProfileStatus($profile): void
    {
        DriverVerification::refresh($profile);
    }
}
