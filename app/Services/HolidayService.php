<?php

namespace App\Services;

use App\Models\Holiday;

class HolidayService
{
    public function getAll($filters = [])
    {
        $query = Holiday::query();

        if (!empty($filters['month'])) {
            $query->whereMonth('date', $filters['month']);
        }

        return $query->orderBy('date', 'desc')->paginate(10)->withQueryString();
    }

    public function create(array $data): Holiday
    {
        return Holiday::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Holiday::findOrFail($id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Holiday::findOrFail($id)->delete();
    }
}
