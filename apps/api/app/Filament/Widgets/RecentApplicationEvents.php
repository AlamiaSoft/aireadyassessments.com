<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentApplicationEvents extends TableWidget
{
    protected static ?string $heading = 'Recent product and system events';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(ApplicationEvent::query()->orderByDesc('occurred_at'))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('occurred_at')->label('Occurred')->dateTime()->sortable(),
                TextColumn::make('event_name')->searchable()->limit(45),
                TextColumn::make('request_id')->label('Request ID')->copyable()->limit(12),
                TextColumn::make('workflow_id')->label('Workflow')->copyable()->limit(12),
                TextColumn::make('properties')->formatStateUsing(fn ($state): string => is_array($state) ? json_encode($state, JSON_UNESCAPED_SLASHES) : (string) $state)->limit(65),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
