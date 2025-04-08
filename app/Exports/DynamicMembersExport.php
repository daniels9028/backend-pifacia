<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DynamicMembersExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }


    public function collection()
    {
        return $this->data->map(function ($book) {
            return collect($book)->map(function ($value) {
                return is_array($value) || is_object($value)
                    ? json_encode($value)
                    : $value;
            });
        });
    }

    public function headings(): array
    {
        return $this->data->isEmpty()
            ? []
            : array_keys($this->data->first()->toArray());
    }
}
