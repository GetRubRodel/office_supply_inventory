<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use App\Support\TinFormatter;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('suppliers.view');
    }

    public static function canCreate(): bool
    {
        // Any role granted the "suppliers.create" permission (Administrator,
        // Supply Officer) may create suppliers — enforced
        // by SupplierPolicy::create. Supplier records are also maintained
        // automatically from Purchase Order data (see SupplierSyncService).
        return CurrentUser::get()?->hasPermissionTo('suppliers.create') ?? false;
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Division Chief may not edit Supplier records.
        if ($user->isDivisionChief()) {
            return false;
        }

        return $user->hasPermissionTo('suppliers.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Supply Officer may not delete Supplier records.
        if ($user->isSupplyOfficer()) {
            return false;
        }

        return $user->hasPermissionTo('suppliers.delete');
    }

    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?int $navigationSort = 2;

    /**
     * Eager-load related Purchase Orders so the PO reference columns
     * (PO Number(s) / PO Date(s)) render without N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['purchaseOrders'])
            ->withCount('purchaseOrders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Supplier Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Contact Number')
                            ->tel()
                            ->maxLength(13)
                            ->regex('/^\+?\d+$/')
                            ->validationMessages([
                                'regex' => 'The Contact Number must contain numbers only.',
                            ])
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '').slice(0, 13)",
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('tin')
                            ->label('TIN Number')
                            ->required()
                            ->maxLength(15)
                            ->formatStateUsing(fn (?string $state): ?string => TinFormatter::format($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : preg_replace('/\D/', '', $state))
                            ->rule(function (): Closure {
                                return function (string $attribute, mixed $value, Closure $fail): void {
                                    $value = (string) $value;

                                    // Let the 'required' rule handle empty values.
                                    if ($value === '') {
                                        return;
                                    }

                                    if (! preg_match('/^[\d-]+$/', $value)) {
                                        $fail('The TIN Number must contain numbers only.');

                                        return;
                                    }

                                    if (! preg_match('/^\d{9}$|^\d{12}$/', preg_replace('/\D/', '', $value))) {
                                        $fail('The TIN Number must be exactly 9 or 12 digits.');
                                    }
                                };
                            })
                            ->extraInputAttributes([
                                'oninput' => "let t=this.value.replace(/\D/g,'').slice(0,12);this.value=t.length?t.match(/.{1,3}/g).join('-'):'';",
                            ]),
                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Supplier Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Contact Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tin')
                    ->label('TIN Number')
                    ->formatStateUsing(fn (?string $state): ?string => TinFormatter::format($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Supplier $record): string => $record->address ?? ''),
                Tables\Columns\TextColumn::make('po_numbers')
                    ->label('PO Number(s)')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->tooltip(fn (Supplier $record): string => $record->po_numbers ?? ''),
                Tables\Columns\TextColumn::make('po_dates')
                    ->label('PO Date(s)')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => ! CurrentUser::get()?->isSupplyOfficer()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => ! CurrentUser::get()?->isSupplyOfficer()),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
