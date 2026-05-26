<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Account;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Suscripciones';
    protected static ?string $modelLabel = 'Suscripción';
    protected static ?string $pluralModelLabel = 'Suscripciones';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('account_id')
                ->label('Cuenta')
                ->options(fn () => Account::orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->required(),
            Forms\Components\Select::make('plan_code')
                ->label('Plan')
                ->options([
                    'trial'   => 'Trial gratuito',
                    'basico'  => 'Básico',
                    'pro'     => 'Pro',
                    'premium' => 'Premium',
                ])
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                    'trialing' => 'Trial activo',
                    'active'   => 'Activo',
                    'inactive' => 'Inactivo',
                    'past_due' => 'Pago pendiente',
                    'canceled' => 'Cancelado',
                ])
                ->required(),
            Forms\Components\DateTimePicker::make('current_period_end_at')
                ->label('Fin del período / Trial'),
            Forms\Components\TextInput::make('stripe_customer_id')
                ->label('Stripe Customer ID')
                ->maxLength(255),
            Forms\Components\TextInput::make('stripe_subscription_id')
                ->label('Stripe Subscription ID')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Cuenta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->getStateUsing(fn (Subscription $record): string =>
                        $record->account?->users()->first()?->email ?? '—'
                    )
                    ->searchable(query: fn ($query, string $search) =>
                        $query->whereHas('account.users', fn ($q) =>
                            $q->where('email', 'like', "%{$search}%")
                        )
                    )
                    ->copyable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->getStateUsing(fn (Subscription $record): string =>
                        $record->account?->users()->first()?->phone ?? '—'
                    )
                    ->copyable(),
                Tables\Columns\TextColumn::make('email_verificado')
                    ->label('Email verificado')
                    ->getStateUsing(fn (Subscription $record): string =>
                        $record->account?->users()->first()?->email_verified_at ? 'Sí' : 'No'
                    )
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Sí' ? 'success' : 'warning'),
                Tables\Columns\BadgeColumn::make('plan_code')
                    ->label('Plan')
                    ->colors([
                        'warning' => 'trial',
                        'primary' => 'basico',
                        'success' => 'pro',
                        'danger'  => 'premium',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'trialing',
                        'success' => 'active',
                        'gray' => 'inactive',
                        'danger'  => ['past_due', 'canceled'],
                    ]),
                Tables\Columns\TextColumn::make('current_period_end_at')
                    ->label('Vence')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('dias_expirado')
                    ->label('Días expirado')
                    ->getStateUsing(function (Subscription $record): string {
                        if ($record->status !== 'inactive') {
                            return '—';
                        }
                        if (!$record->current_period_end_at) {
                            return '—';
                        }
                        $days = (int) now()->diffInDays($record->current_period_end_at, false) * -1;
                        return $days > 0 ? $days . 'd' : '—';
                    })
                    ->color(fn (string $state): string => $state === '—' ? 'gray' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'trialing' => 'Trial activo',
                        'active'   => 'Activo',
                        'inactive' => 'Inactivo',
                        'past_due' => 'Pago pendiente',
                        'canceled' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('plan_code')
                    ->label('Plan')
                    ->options([
                        'trial'   => 'Trial',
                        'basico'  => 'Básico',
                        'pro'     => 'Pro',
                        'premium' => 'Premium',
                    ]),
                Tables\Filters\Filter::make('sin_verificar')
                    ->label('Sin verificar email')
                    ->query(fn ($query) => $query->whereHas('account.users', fn ($q) =>
                        $q->whereNull('email_verified_at')
                    )),
                Tables\Filters\Filter::make('expirados_sin_pagar')
                    ->label('Expirados sin pagar')
                    ->query(fn ($query) => $query->where('status', 'inactive')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar cuenta completa')
                    ->modalSubheading('Se eliminará la suscripción, el restaurante, la cuenta y el usuario. Si tiene suscripción activa en Stripe se cancelará primero. Esta acción no se puede deshacer.')
                    ->modalButton('Sí, eliminar todo')
                    ->action(function (Subscription $record) {
                        // Cancel in Stripe if subscription exists there
                        if ($record->stripe_subscription_id && config('stripe.secret')) {
                            try {
                                $stripe = new StripeClient(config('stripe.secret'));
                                $stripe->subscriptions->cancel($record->stripe_subscription_id);
                            } catch (\Stripe\Exception\InvalidRequestException $e) {
                                // Already canceled in Stripe — safe to continue
                                Log::info('Filament delete: Stripe subscription already canceled.', [
                                    'stripe_subscription_id' => $record->stripe_subscription_id,
                                ]);
                            } catch (\Throwable $e) {
                                Log::error('Filament delete: failed to cancel Stripe subscription — ' . $e->getMessage());
                                Notification::make()
                                    ->title('Error al cancelar en Stripe')
                                    ->body('No se pudo cancelar la suscripción en Stripe. Cancélala manualmente y vuelve a intentarlo.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        // Delete account, restaurants, users and subscription
                        $account = $record->account;
                        if ($account) {
                            $account->subscriptions()->delete();
                            $account->restaurants()->delete();
                            $users = $account->users;
                            $account->users()->detach();
                            $account->delete();
                            foreach ($users as $user) {
                                // Only delete the user if they have no other accounts
                                if ($user->accounts()->count() === 0) {
                                    $user->delete();
                                }
                            }
                        } else {
                            $record->delete();
                        }

                        Notification::make()
                            ->title('Cuenta eliminada correctamente')
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
            'index'  => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit'   => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
