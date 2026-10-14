<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Modules\Ecommerce\Enums\OrderStatus;
use App\Modules\Ecommerce\Exceptions\InvalidOrderTransition;
use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Services\OrderStatusService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->label('Changer le statut')
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (Order $record): bool => auth()->user()->can('update', $record)
                    && $record->status->allowedNext($record->payment_method) !== [])
                ->schema([
                    Select::make('status')
                        ->label('Nouveau statut')
                        ->required()
                        ->options(fn (Order $record): array => collect($record->status->allowedNext($record->payment_method))
                            ->mapWithKeys(fn (OrderStatus $status) => [$status->value => $status->label()])
                            ->all()),
                    Textarea::make('note')
                        ->label('Note (facultative)')
                        ->helperText('Par exemple : « Paiement par virement reçu » ou « Client confirmé par téléphone ».')
                        ->maxLength(255),
                ])
                ->action(function (array $data, Order $record): void {
                    try {
                        app(OrderStatusService::class)->transition(
                            $record,
                            OrderStatus::from($data['status']),
                            auth()->user(),
                            $data['note'] ?? null,
                        );
                    } catch (InvalidOrderTransition $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    $record->refresh();

                    Notification::make()->success()->title('Statut mis à jour')->send();
                }),
        ];
    }
}
