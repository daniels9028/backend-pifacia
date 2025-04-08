<?php

namespace App\Imports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Str;

class DynamicMembersImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $headings = $rows->first()->toArray();
        $dataRows = $rows->slice(1);

        foreach ($dataRows as $row) {
            $rowAssoc = array_combine($headings, $row->toArray());

            Member::updateOrCreate(
                ['id' => (string) Str::uuid()],
                [
                    'name' => $rowAssoc['name'] ?? '',
                    'email' => $rowAssoc['email'] ?? '',
                    'is_active' => filter_var($rowAssoc['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ]
            );
        }
    }
}
