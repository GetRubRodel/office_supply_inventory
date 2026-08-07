<?php

namespace App\Filament\Resources;

use App\Support\CurrentUser;

use App\Filament\Resources\BacResolutionResource\Pages;
use App\Models\BacResolution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BacResolutionResource extends Resource
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('bac_resolutions.view');
    }

    public static function canCreate(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('bac_resolutions.create');
    }

    public static function canEdit(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('bac_resolutions.update');
    }

    public static function canDelete(mixed $record = null): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        return $user->hasPermissionTo('bac_resolutions.delete');
    }

    protected static ?string $model = BacResolution::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Purchase Management';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        $defaultPreamble = "WHEREAS, the Government Procurement Policy Board (GPPB) through its Resolution No. 24-2019 dated 30 October 2019 resolved to approved the inclusion of Section 53.14 in the 2016 Revised IRR of RA 9184 on the Direct Retail Purchase (DRP) of Petroleum Fuel, Oil and Lubricant (POL) products and the amendments to the affected provisions of its Annex \u201cH\u201d entitled \u201cConsolidated Guidelines for the Alternative Methods of Procurement;\n\nWHEREAS, under the aforementioned Section, DRP is allowed where it is found to be the best modality for the procurement of non-bulk POL products\u2026;\n\nWHEREAS, Direct Retail Purchase falls under Negotiated Procurement of Section 53 as an alternative mode of procurement and is therefore subject to the rules and procedures stated in Annex H;\n\nWHEREAS, under Annex H, General Guidelines, item J, the BAC and the HOPE are directed to issue a resolution to delegate to specific officials, personnel, committee or office in the Procuring Entity the conduct of Direct Retail Purchase to efficiently and expeditiously deal with the pressing need sought to be addressed;";

        $defaultOperative = "NOW, THEREFORE, for and in consideration of the foregoing premises, hereby resolve as it is hereby resolved to RECOMMEND to apply Section 53.14 of the 2016 revised IRR of R.A. No. 9184 as mode for the purchase of POL products;";

        $defaultFurther = "FURTHER RESOLVED, to delegate and authorize the Commission on Human Rights Regional Office XII (CHR XII) to procure the fuel allocation for CHR official vehicles under Direct Retail Purchase (DRP) through cash advance and/or reimbursement.";

        $defaultClosing = "SO RESOLVED.";

        return $form
            ->schema([
                Forms\Components\Section::make('BIDS AND AWARDS COMMITTEE (BAC)')
                    ->description('Resolution')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('resolution_no')
                                    ->label('Resolution No.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hint('Auto-generated: MM-SSS')
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('place')
                                    ->label('Place')
                                    ->default('Koronadal City, Philippines')
                                    ->columnSpan(1),
                            ]),
                    ]),

                Forms\Components\Section::make('Title / Subject')
                    ->schema([
                        Forms\Components\Textarea::make('title')
                            ->label('Resolution Title')
                            ->placeholder('AUTHORIZING THE CHR XII TO PROCURE...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Preamble (WHEREAS Clauses)')
                    ->schema([
                        Forms\Components\Textarea::make('preamble')
                            ->label('')
                            ->rows(12)
                            ->default($defaultPreamble)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Operative Part')
                    ->schema([
                        Forms\Components\Textarea::make('operative_part')
                            ->label('NOW, THEREFORE...')
                            ->rows(4)
                            ->default($defaultOperative)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('further_resolved')
                            ->label('FURTHER RESOLVED...')
                            ->rows(4)
                            ->default($defaultFurther)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('closing')
                            ->label('Closing')
                            ->rows(2)
                            ->default($defaultClosing)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('BAC Members')
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\Fieldset::make('Chairperson')
                                    ->schema([
                                        Forms\Components\TextInput::make('chairperson_name')
                                            ->label('Name')
                                            ->default('MIGUEL A. PEÑALOZA'),
                                        Forms\Components\TextInput::make('chairperson_designation')
                                            ->label('Designation')
                                            ->default('Chairperson'),
                                    ]),
                                Forms\Components\Fieldset::make('Vice-Chairperson')
                                    ->schema([
                                        Forms\Components\TextInput::make('vice_chairperson_name')
                                            ->label('Name')
                                            ->default('ATTY. MAE P. GALONG'),
                                        Forms\Components\TextInput::make('vice_chairperson_designation')
                                            ->label('Designation')
                                            ->default('Vice-Chairperson'),
                                    ]),
                                Forms\Components\Fieldset::make('Member 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('member1_name')
                                            ->label('Name')
                                            ->default('ARNOLD B. AUMENTO'),
                                        Forms\Components\TextInput::make('member1_designation')
                                            ->label('Designation')
                                            ->default('Member'),
                                    ]),
                                Forms\Components\Fieldset::make('Member 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('member2_name')
                                            ->label('Name')
                                            ->default('RIZALYN C. ISNANI-CONCHA'),
                                        Forms\Components\TextInput::make('member2_designation')
                                            ->label('Designation')
                                            ->default('Member'),
                                    ]),
                                Forms\Components\Fieldset::make('Member 3')
                                    ->schema([
                                        Forms\Components\TextInput::make('member3_name')
                                            ->label('Name')
                                            ->default('ATTY. REUBEN P. ESCARLAN'),
                                        Forms\Components\TextInput::make('member3_designation')
                                            ->label('Designation')
                                            ->default('Member'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Approved by')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('approved_by_name')
                                    ->label('Name')
                                    ->default('ATTY. KEYSIE M. GOMEZ'),
                                Forms\Components\TextInput::make('approved_by_designation')
                                    ->label('Designation')
                                    ->default('Head of the Procuring Entity'),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('editingUser');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resolution_no')
                    ->label('Res. No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('editingDisplayName')
                    ->label('Editing')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('place')
                    ->label('Place')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Print Resolution')
                    ->icon('heroicon-o-printer')
                    ->url(fn (BacResolution $record): string => route('bac.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('bac_resolutions.update')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('bac_resolutions.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => CurrentUser::get()?->hasPermissionTo('bac_resolutions.delete')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBacResolutions::route('/'),
            'create' => Pages\CreateBacResolution::route('/create'),
            'view' => Pages\ViewBacResolution::route('/{record}'),
            'edit' => Pages\EditBacResolution::route('/{record}/edit'),
        ];
    }
}
