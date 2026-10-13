<?php

namespace App\Filament\Widgets;

use App\Models\Assessment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentAssessments extends TableWidget
{
    protected static ?string $heading = 'Recent assessment workflows';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Assessment::query()->latest())
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('created_at')->label('Received')->dateTime()->sortable(),
                TextColumn::make('id')->label('Workflow ID')->copyable()->limit(12),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'completed' => 'success',
                    'failed' => 'danger',
                    'processing' => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('job_title')->placeholder('Not specified')->limit(32),
                TextColumn::make('failure_code')->placeholder('—')->toggleable(),
                TextColumn::make('completed_at')->label('Completed')->dateTime()->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
