<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Receipt;

class ReceiptNumberGenerator
{
    /**
     * Create a new class instance.
     */
    public function next(Campus $campus): array
    {
        Campus::query()->whereKey($campus->id)->lockForUpdate()->firstOrFail();
        $sequence = ((int) Receipt::where('campus_id', $campus->id)->max('sequence')) + 1;
        $receiptCode = $campus->name === 'Santa Rosa' ? 'SR' : strtoupper($campus->code);

        return [$sequence, sprintf('REC-%s-%06d', $receiptCode, $sequence)];
    }
}
