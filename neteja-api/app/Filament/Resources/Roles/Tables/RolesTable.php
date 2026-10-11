<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Rôle')->badge()->searchable(),
                TextColumn::make('hierarchy_level')->label('Niveau')->sortable(),
                TextColumn::make('permissions_count')->label('Permissions')->counts('permissions'),
                IconColumn::make('is_system')->label('Rôle système')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('hierarchy_level');
    }
}
