<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;
    protected static ?string $navigationIcon = 'heroicon-o-office-building';
    protected static ?string $navigationLabel = 'Restaurantes';
    protected static ?string $modelLabel = 'Restaurante';
    protected static ?string $pluralModelLabel = 'Restaurantes';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('subdomain')
                ->label('Subdominio')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('ai_credits')
                ->label('Créditos IA')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('ai_demo_unlimited')
                ->label('Demo IA ilimitada'),
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
                Tables\Columns\TextColumn::make('subdomain')
                    ->label('Subdominio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('usuarios')
                    ->label('Usuarios')
                    ->getStateUsing(fn (Restaurant $record): string =>
                        $record->account
                            ? $record->account->users->pluck('email')->implode(', ')
                            : '—'
                    )
                    ->wrap()
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('account.users', fn ($q) =>
                            $q->where('email', 'like', "%{$search}%")
                        )
                    ),
                Tables\Columns\BadgeColumn::make('plan')
                    ->label('Plan / Estado')
                    ->getStateUsing(function (Restaurant $record): string {
                        if (! $record->account) return '—';
                        $sub = $record->account->subscriptions()
                            ->orderByDesc('created_at')
                            ->first();
                        return $sub ? strtoupper($sub->plan_code) . ' · ' . $sub->status : '—';
                    })
                    ->colors([
                        'success' => fn ($state) => str_contains((string)$state, 'PREMIUM'),
                        'primary' => fn ($state) => str_contains((string)$state, 'PRO'),
                        'warning' => fn ($state) => str_contains((string)$state, 'BASICO'),
                        'secondary',
                    ]),
                Tables\Columns\TextColumn::make('ai_credits')
                    ->label('Créditos IA')
                    ->sortable(),
                Tables\Columns\IconColumn::make('ai_demo_unlimited')
                    ->label('Demo ∞')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar restaurante')
                    ->modalSubheading('Se eliminarán el restaurante y todos sus productos, categorías y configuración. La cuenta y suscripción del usuario NO se verán afectadas. Esta acción no se puede deshacer.')
                    ->modalButton('Sí, eliminar restaurante')
                    ->action(function (Restaurant $record) {
                        // Products, categories, settings cascade via DB foreign keys.
                        $record->delete();

                        Notification::make()
                            ->title('Restaurante eliminado correctamente')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['account.users', 'account.subscriptions']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit'   => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
