<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Produit')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, Set $set, ?string $state): void {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Adresse (slug)')
                        ->required()
                        ->maxLength(170)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true),
                    Select::make('category_id')
                        ->label('Catégorie')
                        ->relationship('category', 'name', fn (Builder $query) => $query->orderBy('sort_order'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Toggle::make('is_active')
                        ->label('En ligne')
                        ->default(true),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(5)
                        ->columnSpanFull(),
                    FileUpload::make('image_path')
                        ->label('Image principale')
                        ->image()
                        ->disk('public')
                        ->directory('products')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->helperText('Image JPG ou PNG de 2 Mo maximum.')
                        ->columnSpanFull(),
                ]),

            Section::make('Variantes, prix et stock')
                ->description('Un produit sans variante garde une seule ligne « Standard ». Une variante ne se supprime pas : désactivez-la.')
                ->schema([
                    Repeater::make('variants')
                        ->relationship()
                        ->hiddenLabel()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->deletable(false)
                        ->addActionLabel('Ajouter une variante')
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->columns(4)
                        ->schema([
                            TextInput::make('name')
                                ->label('Variante')
                                ->required()
                                ->maxLength(100)
                                ->default('Standard'),
                            TextInput::make('price_fcfa')
                                ->label('Prix')
                                ->required()
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->suffix('FCFA'),
                            TextInput::make('stock')
                                ->label('Stock')
                                ->required()
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->default(0),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),
                ]),
        ]);
    }
}
