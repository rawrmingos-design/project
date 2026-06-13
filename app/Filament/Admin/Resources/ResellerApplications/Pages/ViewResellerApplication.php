<?php

namespace App\Filament\Admin\Resources\ResellerApplications\Pages;

use App\Filament\Admin\Resources\ResellerApplications\ResellerApplicationResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewResellerApplication extends ViewRecord
{
    protected static string $resource = ResellerApplicationResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Application Details')
                    ->schema([
                        TextEntry::make('user.username')
                            ->label('Applicant'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary',
                            }),
                        TextEntry::make('business_name')
                            ->label('Business Name'),
                        TextEntry::make('business_url')
                            ->label('Business URL')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                        TextEntry::make('estimated_transactions')
                            ->label('Est. Monthly Transactions'),
                        TextEntry::make('application_reason')
                            ->label('Application Reason')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                
                Section::make('Documents')
                    ->description('Click on any document to view in full size with zoom controls')
                    ->schema([
                        ViewEntry::make('documents_gallery')
                            ->label('')
                            ->view('filament.resources.reseller-application.documents-gallery')
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Review Information')
                    ->schema([
                        TextEntry::make('applied_at')
                            ->dateTime(),
                        TextEntry::make('approved_at')
                            ->dateTime()
                            ->placeholder('Not approved'),
                        TextEntry::make('rejected_at')
                            ->dateTime()
                            ->placeholder('Not rejected'),
                        TextEntry::make('reviewer.username')
                            ->label('Reviewed By')
                            ->placeholder('Not reviewed'),
                        TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
