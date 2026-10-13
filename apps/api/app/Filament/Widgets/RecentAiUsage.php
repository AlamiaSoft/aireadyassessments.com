<?php

namespace App\Filament\Widgets;

use App\Models\AiUsage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentAiUsage extends TableWidget
{
    protected static ?string $heading = 'Recent AI provider calls';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(AiUsage::query()->latest())
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('created_at')->label('Time')->dateTime()->sortable(),
                TextColumn::make('task'),
                TextColumn::make('provider'),
                TextColumn::make('model')->limit(30),
                TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'succeeded' ? 'success' : ($state === 'failed' ? 'danger' : 'warning')),
                TextColumn::make('latency_ms')->label('Latency (ms)')->numeric(),
                TextColumn::make('input_tokens')->label('Input tokens')->numeric(),
                TextColumn::make('output_tokens')->label('Output tokens')->numeric(),
                TextColumn::make('estimated_cost_usd')->label('Est. USD')->money('USD', divideBy: 1),
                TextColumn::make('fallback_step')->label('Fallback')->placeholder('Primary'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
