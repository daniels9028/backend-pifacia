<?php

namespace App\Imports;

use App\Models\Borrowing;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Str;

class DynamicBorrowingImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $headings = $rows->first()->toArray();
        $dataRows = $rows->slice(1);

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine($headings, $row->toArray());

            Borrowing::updateOrCreate(
                ['id' => (string) Str::uuid()],
                [
                    'book_id' => $rowAssoc['book_id'] ?? '',
                    'member_id' => $rowAssoc['member_id'] ?? '',
                    'borrowed_at' => $rowAssoc['borrowed_at'] ?? '',
                    'returned' => filter_var($rowAssoc['returned'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'notes' => json_decode($rowAssoc['notes']) ?? [],
                ]
            );
        }
    }
}
