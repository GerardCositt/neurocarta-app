<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label('Teléfono')
                ->maxLength(30),
            Forms\Components\DateTimePicker::make('email_verified_at')
                ->label('Email verificado'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verificado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('verified')
                    ->label('Solo verificados')
                    ->query(fn ($query) => $query->whereNotNull('email_verified_at')),
                Tables\Filters\Filter::make('unverified')
                    ->label('Sin verificar')
                    ->query(fn ($query) => $query->whereNull('email_verified_at')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar cuenta completa')
                    ->modalSubheading('Se eliminará el usuario, su cuenta, restaurante y suscripción. Si tiene suscripción activa en Stripe se cancelará primero. Esta acción no se puede deshacer.')
                    ->modalButton('Sí, eliminar todo')
                    ->action(function (User $record) {
                        $account = $record->accounts()->first();

                        if ($account) {
                            $subscription = $account->subscriptions()->latest()->first();

                            // Cancel in Stripe if subscription exists there
                            if ($subscription?->stripe_subscription_id && config('stripe.secret')) {
                                try {
                                    $stripe = new StripeClient(config('stripe.secret'));
                                    $stripe->subscriptions->cancel($subscription->stripe_subscription_id);
                                } catch (\Stripe\Exception\InvalidRequestException $e) {
                                    Log::info('Filament user delete: Stripe subscription already canceled.', [
                                        'stripe_subscription_id' => $subscription->stripe_subscription_id,
                                    ]);
                                } catch (\Throwable $e) {
                                    Log::error('Filament user delete: failed to cancel Stripe subscription — ' . $e->getMessage());
                                    Notification::make()
                                        ->title('Error al cancelar en Stripe')
                                        ->body('No se pudo cancelar la suscripción en Stripe. Cancélala manualmente y vuelve a intentarlo.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }

                            $account->subscriptions()->delete();
                            $account->restaurants()->delete();
                            $account->users()->detach();
                            $account->delete();
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Usuario y cuenta eliminados correctamente')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
