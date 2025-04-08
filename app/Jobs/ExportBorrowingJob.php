<?php

namespace App\Jobs;

use App\Exports\DynamicBorrowingExport;
use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportBorrowingJob implements ShouldQueue
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
        $borrowing = Borrowing::select($this->fields)->get()->filter();

        Excel::store(new DynamicBorrowingExport($borrowing), $this->filename, 'public');
    }
}
