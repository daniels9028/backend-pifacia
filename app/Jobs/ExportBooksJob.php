<?php

namespace App\Jobs;

use App\Exports\DynamicBooksExport;
use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportBooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $fields;
    protected $filename;

    public function __construct($userId, array $fields, $filename)
    {
        $this->userId = $userId;
        $this->fields = $fields;
        $this->filename = $filename;
    }

    public function handle(): void
    {
        $books = Book::select($this->fields)->get()->filter();

        Excel::store(new DynamicBooksExport($books), $this->filename, 'public');
    }
}
