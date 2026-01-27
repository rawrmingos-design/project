<?php

namespace App\Filament\Admin\Resources\Ratings\Pages;

use App\Filament\Admin\Resources\Ratings\RatingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRating extends CreateRecord
{
    protected static string $resource = RatingResource::class;
}
