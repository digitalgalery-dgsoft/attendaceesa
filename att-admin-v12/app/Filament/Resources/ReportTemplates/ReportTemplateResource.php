<?php

namespace App\Filament\Resources\ReportTemplates;

use App\Filament\Resources\ReportTemplates\Pages\CreateReportTemplate;
use App\Filament\Resources\ReportTemplates\Pages\EditReportTemplate;
use App\Filament\Resources\ReportTemplates\Pages\ListReportTemplates;
use App\Filament\Resources\ReportTemplates\Schemas\ReportTemplateForm;
use App\Filament\Resources\ReportTemplates\Tables\ReportTemplatesTable;
use App\Models\ReportTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReportTemplateResource extends Resource
{
    protected static ?string $model = ReportTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|\UnitEnum|null $navigationGroup = 'Reporting & Kunjungan';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Form Builder (Template Laporan)';
    protected static ?string $modelLabel = 'Template Form Pelaporan';
    protected static ?string $pluralModelLabel = 'Template Form Pelaporan';

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return ReportTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['principals', 'principal']);
        return \App\Traits\ScopesUserData::applyUserAccessScope($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportTemplates::route('/'),
            'create' => CreateReportTemplate::route('/create'),
            'edit' => EditReportTemplate::route('/{record}/edit'),
        ];
    }
}
