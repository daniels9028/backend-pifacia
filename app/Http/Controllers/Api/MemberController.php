<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExportMembersJob;
use App\Jobs\ImportMembersJob;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::when($request->search, function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%');
        })
            ->orderBy('name')
            ->paginate(10);

        return response()->json($members);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:members,email',
        ]);

        $member = Member::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Member berhasil ditambahkan.',
            'data'    => $member
        ], 201);
    }

    public function show($id)
    {
        $member = Member::findOrFail($id);
        return response()->json($member);
    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:members,email,' . $member->id,
            'is_active' => 'boolean',
        ]);

        $member->update($request->only(['name', 'email', 'is_active']));

        return response()->json([
            'message' => 'Member berhasil diperbarui.',
            'data' => $member
        ]);
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return response()->json(['message' => 'Member berhasil dihapus (soft delete).']);
    }

    public function export(Request $request)
    {
        $request->validate([
            'fields' => 'required|array|min:1',
        ]);

        $userId = auth()->id();

        $filename = "exports/members_{$userId}_" . time() . ".xlsx";

        ExportMembersJob::dispatch($userId, $request->fields, $filename);

        $url = Storage::url($filename);

        return response()->json([
            'message' => 'Export is being processed.',
            'file_url' => $url
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $path = $request->file('file')->store('imports', 'public');

        ImportMembersJob::dispatch($path);

        return response()->json(['message' => 'Import job dispatched.']);
    }

    public function audit()
    {
        $audits = Audit::with(['user', 'auditable'])
            ->where('auditable_type', 'members')
            ->latest()
            ->get()
            ->map(function ($audit) {
                $model = optional($audit->auditable);

                return [
                    'user' => optional($audit->user)->name ?? 'System',
                    'event' => $audit->event,
                    'model_type' => class_basename($audit->auditable_type),
                    'id' => $audit->auditable_id . $audit->id,
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'created_at' => $audit->created_at->toDateTimeString(),
                ];
            });

        return response()->json(['data' => $audits]);
    }
}
