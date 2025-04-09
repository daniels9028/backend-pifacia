<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExportBorrowingJob;
use App\Jobs\ImportBorrowingJob;
use App\Models\Borrowing;
use App\Models\Book;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;

class BorrowingController extends Controller
{
    public function getFormOptions()
    {
        $books = Book::get(['id', 'title', 'author']);
        $members = Member::where('is_active', true)->get(['id', 'name', 'email']);

        return response()->json([
            'books' => $books,
            'members' => $members,
        ]);
    }

    public function index(Request $request)
    {
        $borrowings = Borrowing::with(['book', 'member'])
            ->when($request->search, function ($query) use ($request) {
                $query->whereHas('member', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                });
            })
            ->orderBy('borrowed_at', 'desc')
            ->paginate(10);

        return response()->json($borrowings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'book_id'     => 'required|exists:books,id',
            'member_id'   => 'required|exists:members,id',
            'borrowed_at' => 'required|date',
            'attachment'  => 'nullable|file|mimes:pdf|max:512',
        ]);

        $data = $request->only(['book_id', 'member_id', 'borrowed_at']);
        $data['returned'] = false;
        $data['notes'] = $request->input('notes') ?? [];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments');
        }

        $borrowing = Borrowing::create($data);

        // Set buku jadi tidak tersedia
        Book::where('id', $data['book_id'])->update(['is_available' => false]);

        return response()->json([
            'message' => 'Peminjaman berhasil ditambahkan.',
            'data' => $borrowing
        ], 201);
    }

    public function show($id)
    {
        $borrowing = Borrowing::with(['book', 'member'])->findOrFail($id);
        return response()->json($borrowing);
    }

    public function update(Request $request, $id)
    {
        $borrowing = Borrowing::findOrFail($id);

        $request->validate([
            'book_id'     => 'required|exists:books,id',
            'member_id'   => 'required|exists:members,id',
            'borrowed_at' => 'required|date',
            'attachment'  => 'nullable|file|mimes:pdf|max:512',
        ]);

        $data = $request->only(['book_id', 'member_id', 'borrowed_at']);
        $data['notes'] = $request->input('notes') ?? $borrowing->notes;

        if ($request->hasFile('attachment')) {
            if ($borrowing->attachment) {
                Storage::delete($borrowing->attachment);
            }
            $data['attachment'] = $request->file('attachment')->store('attachments');
        }

        $borrowing->update($data);

        // Update status ketersediaan buku
        // Book::where('id', $borrowing->book_id)->update([
        //     'is_available' => !$data['returned']
        // ]);

        return response()->json([
            'message' => 'Peminjaman berhasil diperbarui.',
            'data' => $borrowing
        ]);
    }

    public function destroy($id)
    {
        $borrowing = Borrowing::findOrFail($id);
        $borrowing->delete();

        return response()->json([
            'message' => 'Peminjaman berhasil dihapus (soft delete).'
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'fields' => 'required|array|min:1',
        ]);

        $userId = auth()->id();

        $filename = "exports/borrowing_{$userId}_" . time() . ".xlsx";

        ExportBorrowingJob::dispatch($userId, $request->fields, $filename);

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

        ImportBorrowingJob::dispatch($path);

        return response()->json(['message' => 'Import job dispatched.']);
    }

    public function audit()
    {
        $audits = Audit::with(['user', 'auditable'])
            ->where('auditable_type', 'borrowing')
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
