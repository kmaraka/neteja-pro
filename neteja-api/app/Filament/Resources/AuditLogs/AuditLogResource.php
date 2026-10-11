<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Modules\Auth\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = "entrée du journal d'audit";

    protected static ?string $pluralModelLabel = "journal d'audit";

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Utilisateur')->placeholder('Système'),
                TextColumn::make('module')->label('Module')->badge(),
                TextColumn::make('action')->label('Action')->searchable(),
                TextColumn::make('subject_type')
                    ->label('Concerne')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                TextColumn::make('subject_id')->label('N°'),
                TextColumn::make('details')
                    ->label('Détails')
                    ->state(fn (AuditLog $record): string => $record->details
                        ? json_encode($record->details, JSON_UNESCAPED_UNICODE)
                        : '')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label('Module')
                    ->options(fn (): array => AuditLog::query()->distinct()->orderBy('module')->pluck('module', 'module')->all()),
            ])
            ->defaultSort('id', 'desc');
    }

    // Un journal d'audit ne se crée pas à la main.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
