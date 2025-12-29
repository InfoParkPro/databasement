<?php

namespace App\Queries;

use App\Models\BackupJob;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class BackupJobQuery
{
    private const RELATIONSHIPS = [
        'snapshot.databaseServer',
        'snapshot.triggeredBy',
        'restore.snapshot.databaseServer',
        'restore.targetServer',
        'restore.triggeredBy',
    ];

    /**
     * @return QueryBuilder<BackupJob>
     */
    public static function make(): QueryBuilder
    {
        return QueryBuilder::for(BackupJob::class)
            ->with(self::RELATIONSHIPS)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('type', function (Builder $query, $value) {
                    if ($value === 'backup') {
                        $query->whereHas('snapshot');
                    } elseif ($value === 'restore') {
                        $query->whereHas('restore');
                    }
                }),
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    self::applySearch($query, $value);
                }),
            ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
                AllowedSort::field('started_at'),
                AllowedSort::field('completed_at'),
                AllowedSort::field('status'),
            ])
            ->defaultSort('-created_at');
    }

    /**
     * Build query from manual parameters (for Livewire).
     *
     * @param  array<string>  $statusFilter
     * @return Builder<BackupJob>
     */
    public static function buildFromParams(
        ?string $search = null,
        array $statusFilter = [],
        string $typeFilter = 'all',
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): Builder {
        return BackupJob::query()
            ->with(self::RELATIONSHIPS)
            ->when($search, function (Builder $query) use ($search) {
                self::applySearch($query, $search);
            })
            ->when(! empty($statusFilter), function (Builder $query) use ($statusFilter) {
                $query->whereIn('status', $statusFilter);
            })
            ->when($typeFilter !== 'all', function (Builder $query) use ($typeFilter) {
                if ($typeFilter === 'backup') {
                    $query->whereHas('snapshot');
                } else {
                    $query->whereHas('restore');
                }
            })
            ->orderBy($sortColumn, $sortDirection);
    }

    /**
     * @param  Builder<BackupJob>  $query
     */
    private static function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search) {
            $q->whereHas('snapshot.databaseServer', function (Builder $sq) use ($search) {
                $sq->whereRaw('name LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('host LIKE ?', ["%{$search}%"]);
            })
                ->orWhereHas('snapshot', function (Builder $sq) use ($search) {
                    $sq->whereRaw('database_name LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('restore.targetServer', function (Builder $sq) use ($search) {
                    $sq->whereRaw('name LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('restore', function (Builder $sq) use ($search) {
                    $sq->whereRaw('schema_name LIKE ?', ["%{$search}%"]);
                });
        });
    }
}
