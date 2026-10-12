<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->label('')->disk('public')->square()->size(48),
                TextColumn::make('name')->label('Produit')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Catégorie')->sortable(),
                TextColumn::make('variants_min_price_fcfa')
                    ->label('À partir de')
                    ->min('variants', 'price_fcfa')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' FCFA')
                    ->sortable(),
                TextColumn::make('variants_sum_stock')
                    ->label('Stock')
                    ->sum('variants', 'stock')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (int) $state === 0 => 'danger',
                        (int) $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                ToggleColumn::make('is_active')->label('En ligne'),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Catégorie')->relationship('category', 'name'),
                TernaryFilter::make('is_active')->label('En ligne'),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }
}
