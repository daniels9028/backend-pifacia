<?php

namespace App\Imports;

use App\Models\Book;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Str;

class DynamicBooksImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $headings = $rows->first()->toArray();
        $dataRows = $rows->slice(1);

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine($headings, $row->toArray());

            Book::updateOrCreate(
                ['id' => (string) Str::uuid()],
                [
                    'title' => $rowAssoc['title'] ?? '',
                    'author' => $rowAssoc['author'] ?? '',
                    'extra_info' => json_decode($rowAssoc['extra_info']) ?? [],
                    'is_available' => filter_var($rowAssoc['is_available'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ]
            );
        }
    }
}
