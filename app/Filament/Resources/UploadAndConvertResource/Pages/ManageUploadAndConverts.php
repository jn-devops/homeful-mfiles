<?php

namespace App\Filament\Resources\UploadAndConvertResource\Pages;

use App\Filament\Resources\UploadAndConvertResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUploadAndConverts extends ManageRecords
{
    protected static string $resource = UploadAndConvertResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }
}
