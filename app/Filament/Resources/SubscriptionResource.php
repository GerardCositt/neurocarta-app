<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

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
                        'secondary' => 'inactive',
                        'danger'  => ['past_due', 'canceled'],
                    ]),
                Tables\Columns\TextColumn::make('current_period_end_at')
                    ->label('Vence')
                    ->dateTime('d/m/Y')
                    ->sortable(),
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
