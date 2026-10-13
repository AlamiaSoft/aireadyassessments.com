<?php

namespace App\Filament\Widgets;

use App\Models\AiUsage;
use App\Models\ApplicationEvent;
use App\Models\Assessment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsageOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now('UTC')->startOfDay();
        $completed = Assessment::query()->where('status', 'completed');
        $eventsToday = ApplicationEvent::query()->where('occurred_at', '>=', $today);
        $aiToday = AiUsage::query()->where('created_at', '>=', $today);

        return [
            Stat::make('Assessments today', (string) Assessment::query()->where('created_at', '>=', $today)->count())
                ->description('Queued, processing and terminal requests')
                ->color('primary'),
            Stat::make('Completed all time', (string) $completed->count())
                ->description('Successful assessment workflows')
                ->color('success'),
            Stat::make('Failed all time', (string) Assessment::query()->where('status', 'failed')->count())
                ->description('Investigate error codes and event history')
                ->color('danger'),
            Stat::make('AI cost today (USD)', '$'.number_format((float) $aiToday->sum('estimated_cost_usd'), 4))
                ->description('Estimated/provider-reported cost in usage ledger')
                ->color('warning'),
            Stat::make('AI calls today', (string) $aiToday->count())
                ->description('Includes failed provider attempts')
                ->color('primary'),
            Stat::make('Events today', (string) $eventsToday->count())
                ->description('Business telemetry events recorded')
                ->color('primary'),
        ];
    }
}
