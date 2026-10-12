<?php

namespace App\Filament\Resources\DeliveryZones;

use App\Filament\Resources\DeliveryZones\Pages\CreateDeliveryZone;
use App\Filament\Resources\DeliveryZones\Pages\EditDeliveryZone;
use App\Filament\Resources\DeliveryZones\Pages\ListDeliveryZones;
use App\Modules\Ecommerce\Models\DeliveryZone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Boutique';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'zone de livraison';

    protected static ?string $pluralModelLabel = 'zones de livraison';

    protected static ?string $recordTitleAttribute = 'commune';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('commune')
                        ->label('Commune')
                        ->required()
                        ->maxLength(80)
                        ->unique(ignoreRecord: true),
                    TextInput::make('fee_fcfa')
                        ->label('Frais de livraison')
                        ->required()
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->suffix('FCFA'),
                    TextInput::make('estimated_delay')
                        ->label('Délai estimé')
                        ->maxLength(80)
                        ->placeholder('24 à 48 h'),
                    Toggle::make('is_active')
                        ->label('Livraison disponible')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commune')->label('Commune')->searchable()->sortable(),
                TextColumn::make('fee_fcfa')->label('Frais')->numeric(decimalPlaces: 0)->suffix(' FCFA')->sortable(),
                TextColumn::make('estimated_delay')->label('Délai')->placeholder('—'),
                IconColumn::make('is_active')->label('Disponible')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('commune');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryZones::route('/'),
            'create' => CreateDeliveryZone::route('/create'),
            'edit' => EditDeliveryZone::route('/{record}/edit'),
        ];
    }
}
