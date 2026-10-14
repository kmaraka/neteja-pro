<?php

namespace App\Filament\Resources\PromoCodes;

use App\Filament\Resources\PromoCodes\Pages\CreatePromoCode;
use App\Filament\Resources\PromoCodes\Pages\EditPromoCode;
use App\Filament\Resources\PromoCodes\Pages\ListPromoCodes;
use App\Modules\Ecommerce\Models\PromoCode;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Boutique';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'code promo';

    protected static ?string $pluralModelLabel = 'codes promo';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Code')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->maxLength(40)
                        ->regex('/^[A-Za-z0-9_-]+$/')
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (string $state): string => strtoupper(trim($state)))
                        ->helperText('Lettres, chiffres, tirets. Enregistré en majuscules. Exemple : BIENVENUE10'),
                    Toggle::make('is_active')
                        ->label('Actif')
                        ->default(true),
                    Select::make('type')
                        ->label('Type de remise')
                        ->options([
                            PromoCode::TYPE_PERCENT => 'Pourcentage',
                            PromoCode::TYPE_FIXED => 'Montant fixe (FCFA)',
                        ])
                        ->required()
                        ->live(),
                    TextInput::make('value')
                        ->label('Valeur')
                        ->required()
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(fn (Get $get): ?int => $get('type') === PromoCode::TYPE_PERCENT ? 100 : null)
                        ->suffix(fn (Get $get): string => $get('type') === PromoCode::TYPE_PERCENT ? '%' : 'FCFA'),
                ]),

            Section::make('Validité et limite')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at')->label('Début')->seconds(false),
                    DateTimePicker::make('ends_at')->label('Fin')->seconds(false)->after('starts_at'),
                    TextInput::make('max_uses')
                        ->label("Nombre maximal d'utilisations")
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->helperText('Laissez vide pour un usage illimité.'),
                    TextInput::make('uses_count')
                        ->label('Utilisations à ce jour')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->badge()->searchable()->sortable(),
                TextColumn::make('value')
                    ->label('Remise')
                    ->formatStateUsing(fn ($state, PromoCode $record): string => $record->type === PromoCode::TYPE_PERCENT
                        ? "{$state} %"
                        : number_format((int) $state, 0, ',', ' ').' FCFA'),
                TextColumn::make('starts_at')->label('Début')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('ends_at')->label('Fin')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('uses_count')
                    ->label('Utilisations')
                    ->formatStateUsing(fn ($state, PromoCode $record): string => $record->max_uses
                        ? "{$state} / {$record->max_uses}"
                        : (string) $state),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromoCodes::route('/'),
            'create' => CreatePromoCode::route('/create'),
            'edit' => EditPromoCode::route('/{record}/edit'),
        ];
    }
}
