<?php

namespace App\Filament\Admin\Resources\RewardRedemptions\Pages;

use App\Filament\Admin\Resources\RewardRedemptions\RewardRedemptionResource;
use App\Models\RewardRedemption;
use Filament\Resources\Pages\EditRecord;

class EditRewardRedemption extends EditRecord
{
    protected static string $resource = RewardRedemptionResource::class;

    /**
     * Isi otomatis jejak waktu status saat status berubah maju.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $now = now();
        $status = $data['status'] ?? null;

        if ($status === RewardRedemption::STATUS_PROCESSING && empty($this->record->processed_at)) {
            $data['processed_at'] = $now;
        }
        if ($status === RewardRedemption::STATUS_SHIPPED && empty($this->record->shipped_at)) {
            $data['shipped_at'] = $now;
        }
        if ($status === RewardRedemption::STATUS_COMPLETED && empty($this->record->completed_at)) {
            $data['completed_at'] = $now;
        }

        return $data;
    }
}
