<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages;
use App\Models\Restaurant;
use Filament\Forms;
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
            'index'  => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit'   => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
