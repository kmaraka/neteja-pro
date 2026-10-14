<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Enums\PaymentMethod;
use App\Modules\Ecommerce\Models\Order;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Boutique';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?string $recordTitleAttribute = 'number';

    // Pastille dans le menu : nombre de commandes en attente.
    public static function getNavigationBadge(): ?string
    {
        $count = Order::where('status', OrderStatus::PendingPayment->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Commande')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Client')->searchable(),
                TextColumn::make('ship_commune')->label('Commune')->searchable(),
                TextColumn::make('total_fcfa')->label('Total')->numeric(decimalPlaces: 0)->suffix(' FCFA')->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('payment_method')
                    ->label('Paiement')
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                TextColumn::make('created_at')->label('Passée le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])->all()),
                SelectFilter::make('payment_method')
                    ->label('Paiement')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m) => [$m->value => $m->label()])->all()),
                Filter::make('period')
                    ->label('Période')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Commande')
                ->columns(3)
                ->schema([
                    TextEntry::make('number')->label('Numéro')->copyable(),
                    TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                        ->color(fn (OrderStatus $state): string => $state->color()),
                    TextEntry::make('payment_method')
                        ->label('Paiement')
                        ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                    TextEntry::make('user.name')->label('Client'),
                    TextEntry::make('created_at')->label('Passée le')->dateTime('d/m/Y H:i'),
                    TextEntry::make('paid_at')->label('Payée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                ]),

            Section::make('Livraison')
                ->columns(2)
                ->schema([
                    TextEntry::make('ship_recipient_name')->label('Destinataire'),
                    TextEntry::make('ship_phone')->label('Téléphone'),
                    TextEntry::make('ship_commune')->label('Commune'),
                    TextEntry::make('ship_address_line')->label('Adresse'),
                    TextEntry::make('ship_additional_info')->label('Indications')->placeholder('—'),
                    TextEntry::make('customer_note')->label('Note du client')->placeholder('—'),
                ]),

            Section::make('Articles')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('product_name')->label('Produit'),
                            TextEntry::make('variant_name')->label('Variante'),
                            TextEntry::make('quantity')->label('Quantité'),
                            TextEntry::make('line_total_fcfa')->label('Total')->numeric(decimalPlaces: 0)->suffix(' FCFA'),
                        ]),
                ]),

            Section::make('Montants')
                ->columns(4)
                ->schema([
                    TextEntry::make('subtotal_fcfa')->label('Sous-total')->numeric(decimalPlaces: 0)->suffix(' FCFA'),
                    TextEntry::make('discount_fcfa')->label('Remise')->numeric(decimalPlaces: 0)->suffix(' FCFA'),
                    TextEntry::make('delivery_fee_fcfa')->label('Livraison')->numeric(decimalPlaces: 0)->suffix(' FCFA'),
                    TextEntry::make('total_fcfa')->label('Total')->numeric(decimalPlaces: 0)->suffix(' FCFA'),
                ]),

            Section::make('Historique des statuts')
                ->schema([
                    RepeatableEntry::make('statusHistories')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                            TextEntry::make('to_status')
                                ->label('Statut')
                                ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                            TextEntry::make('user.name')->label('Par')->placeholder('Système'),
                            TextEntry::make('note')->label('Note')->placeholder('—'),
                        ]),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
