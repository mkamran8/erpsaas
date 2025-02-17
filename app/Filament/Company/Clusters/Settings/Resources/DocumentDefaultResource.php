<?php

namespace App\Filament\Company\Clusters\Settings\Resources;

use App\Enums\Setting\Font;
use App\Enums\Setting\PaymentTerms;
use App\Enums\Setting\Template;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Company\Clusters\Settings\Resources\DocumentDefaultResource\Pages;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\DocumentDefault as InvoiceModel;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentDefaultResource extends Resource
{
    protected static ?string $model = DocumentDefault::class;

    protected static ?string $cluster = Settings::class;

    public static function form(Form $form): Form
    {
        return $form
            ->live()
            ->schema([
                self::getGeneralSection(),
                self::getContentSection(),
                self::getTemplateSection(),
            ]);
    }

    public static function getGeneralSection(): Forms\Components\Component
    {
        return Forms\Components\Section::make('General')
            ->schema([
                Forms\Components\TextInput::make('number_prefix')
                    ->localizeLabel()
                    ->nullable(),
                Forms\Components\Select::make('number_digits')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(InvoiceModel::availableNumberDigits()),
                Forms\Components\TextInput::make('number_next')
                    ->softRequired()
                    ->localizeLabel()
                    ->mask(static function (Get $get) {
                        return str_repeat('9', $get('number_digits'));
                    })
                    ->hint(function (Get $get, $state, DocumentDefault $record) {
                        $number_prefix = $get('number_prefix');
                        $number_digits = $get('number_digits');
                        $number_next = $state;

                        return $record->getNumberNext(true, true, $number_prefix, $number_digits, $number_next);
                    }),
                Forms\Components\Select::make('payment_terms')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(PaymentTerms::class),
            ])->columns();
    }

    public static function getContentSection(): Forms\Components\Component
    {
        return Forms\Components\Section::make('Content')
            ->schema([
                Forms\Components\TextInput::make('header')
                    ->localizeLabel()
                    ->nullable(),
                Forms\Components\TextInput::make('subheader')
                    ->localizeLabel()
                    ->nullable(),
                Forms\Components\Textarea::make('terms')
                    ->localizeLabel()
                    ->nullable(),
                Forms\Components\Textarea::make('footer')
                    ->localizeLabel('Footer')
                    ->nullable(),
            ])->columns();
    }

    public static function getTemplateSection(): Component
    {
        return Forms\Components\Section::make('Template')
            ->description('Choose the template and edit the column names.')
            ->schema([
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->openable()
                            ->maxSize(1024)
                            ->localizeLabel()
                            ->visibility('public')
                            ->disk('public')
                            ->directory('logos/document')
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio('3:2')
                            ->panelAspectRatio('3:2')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('center bottom')
                            ->uploadButtonPosition('center bottom')
                            ->uploadProgressIndicatorPosition('center bottom')
                            ->getUploadedFileNameForStorageUsing(
                                static fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(Auth::user()->currentCompany->id . '_'),
                            )
                            ->extraAttributes([
                                'class' => 'aspect-[3/2] w-[9.375rem] max-w-full',
                            ])
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/gif']),
                        Forms\Components\Checkbox::make('show_logo')
                            ->localizeLabel(),
                        Forms\Components\ColorPicker::make('accent_color')
                            ->localizeLabel(),
                        Forms\Components\Select::make('font')
                            ->softRequired()
                            ->localizeLabel()
                            ->allowHtml()
                            ->options(
                                collect(Font::cases())
                                    ->mapWithKeys(static fn ($case) => [
                                        $case->value => "<span style='font-family:{$case->getLabel()}'>{$case->getLabel()}</span>",
                                    ]),
                            ),
                        Forms\Components\Select::make('template')
                            ->softRequired()
                            ->localizeLabel()
                            ->options(Template::class),
                        Forms\Components\Select::make('item_name.option')
                            ->softRequired()
                            ->localizeLabel('Item name')
                            ->options(InvoiceModel::getAvailableItemNameOptions())
                            ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                                if ($state !== 'other' && $old === 'other' && filled($get('item_name.custom'))) {
                                    $set('item_name.old_custom', $get('item_name.custom'));
                                    $set('item_name.custom', null);
                                }

                                if ($state === 'other' && $old !== 'other') {
                                    $set('item_name.custom', $get('item_name.old_custom'));
                                }
                            }),
                        Forms\Components\TextInput::make('item_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('item_name.option') !== 'other')
                            ->nullable(),
                        Forms\Components\Select::make('unit_name.option')
                            ->softRequired()
                            ->localizeLabel('Unit name')
                            ->options(InvoiceModel::getAvailableUnitNameOptions())
                            ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                                if ($state !== 'other' && $old === 'other' && filled($get('unit_name.custom'))) {
                                    $set('unit_name.old_custom', $get('unit_name.custom'));
                                    $set('unit_name.custom', null);
                                }

                                if ($state === 'other' && $old !== 'other') {
                                    $set('unit_name.custom', $get('unit_name.old_custom'));
                                }
                            }),
                        Forms\Components\TextInput::make('unit_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('unit_name.option') !== 'other')
                            ->nullable(),
                        Forms\Components\Select::make('price_name.option')
                            ->softRequired()
                            ->localizeLabel('Price name')
                            ->options(InvoiceModel::getAvailablePriceNameOptions())
                            ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                                if ($state !== 'other' && $old === 'other' && filled($get('price_name.custom'))) {
                                    $set('price_name.old_custom', $get('price_name.custom'));
                                    $set('price_name.custom', null);
                                }

                                if ($state === 'other' && $old !== 'other') {
                                    $set('price_name.custom', $get('price_name.old_custom'));
                                }
                            }),
                        Forms\Components\TextInput::make('price_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('price_name.option') !== 'other')
                            ->nullable(),
                        Forms\Components\Select::make('amount_name.option')
                            ->softRequired()
                            ->localizeLabel('Amount name')
                            ->options(InvoiceModel::getAvailableAmountNameOptions())
                            ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                                if ($state !== 'other' && $old === 'other' && filled($get('amount_name.custom'))) {
                                    $set('amount_name.old_custom', $get('amount_name.custom'));
                                    $set('amount_name.custom', null);
                                }

                                if ($state === 'other' && $old !== 'other') {
                                    $set('amount_name.custom', $get('amount_name.old_custom'));
                                }
                            }),
                        Forms\Components\TextInput::make('amount_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('amount_name.option') !== 'other')
                            ->nullable(),
                    ])->columnSpan(1),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\ViewField::make('preview.default')
                            ->columnSpan(2)
                            ->hiddenLabel()
                            ->visible(static fn (Get $get) => $get('template') === 'default')
                            ->view('filament.company.components.invoice-layouts.default'),
                        Forms\Components\ViewField::make('preview.modern')
                            ->columnSpan(2)
                            ->hiddenLabel()
                            ->visible(static fn (Get $get) => $get('template') === 'modern')
                            ->view('filament.company.components.invoice-layouts.modern'),
                        Forms\Components\ViewField::make('preview.classic')
                            ->columnSpan(2)
                            ->hiddenLabel()
                            ->visible(static fn (Get $get) => $get('template') === 'classic')
                            ->view('filament.company.components.invoice-layouts.classic'),
                    ])->columnSpan(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('number_prefix'),
                Tables\Columns\TextColumn::make('number_next')
                    ->label('Next Number'),
                Tables\Columns\TextColumn::make('template')
                    ->badge(),
                Tables\Columns\IconColumn::make('show_logo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListDocumentDefaults::route('/'),
            'create' => Pages\CreateDocumentDefault::route('/create'),
            'edit' => Pages\EditDocumentDefault::route('/{record}/edit'),
        ];
    }
}
