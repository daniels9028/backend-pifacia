<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExportBooksJob;
use App\Jobs\ImportBooksJob;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::when($request->search, function ($q) use ($request) {
            $q->where('title', 'like', '%' . $request->search . '%');
        })
            ->orderBy('title')
            ->paginate(10);

        return response()->json($books);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'author'     => 'required|string|max:255',
            'extra_info' => 'nullable|array',
        ]);

        $book = Book::create([
            'title'       => $request->title,
            'author'      => $request->author,
            'extra_info'  => $request->extra_info ?? [],
            'is_available' => true,
        ]);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan.',
            'data'    => $book
        ], 201);
    }

    public function show($id)
    {
        $book = Book::findOrFail($id);
        return response()->json($book);
    }

    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $request->validate([
            'title'      => 'required|string|max:255',
            'author'     => 'required|string|max:255',
            'extra_info' => 'nullable|array',
            'is_available' => 'boolean',
        ]);

        $book->update($request->only(['title', 'author', 'extra_info', 'is_available']));

        return response()->json([
            'message' => 'Buku berhasil diperbarui.',
            'data' => $book
        ]);
    }

    public function destroy($id)
    {
        $book = Book::findOrFail($id);
        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus (soft delete).']);
    }

    public function export(Request $request)
    {
        $request->validate([
            'fields' => 'required|array|min:1',
        ]);

        $userId = auth()->id();

        $filename = "exports/books_{$userId}_" . time() . ".xlsx";

        ExportBooksJob::dispatch($userId, $request->fields, $filename);

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

        ImportBooksJob::dispatch($path);

        return response()->json(['message' => 'Import job dispatched.']);
    }
}
