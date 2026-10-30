<?php

namespace App\Filament\Admin\Resources\Rewards\Pages;

use App\Filament\Admin\Resources\Rewards\RewardResource;
use App\Models\Reward;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateReward extends CreateRecord
{
    protected static string $resource = RewardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $base = Str::slug($data['name'] ?? '') ?: 'reward';
            $slug = $base;
            $i = 1;
            while (Reward::where('slug', $slug)->exists()) {
                $slug = $base . '-' . (++$i);
            }
            $data['slug'] = $slug;
        }

        return $data;
    }
}
