<?php

namespace App\Filament\Resources\ReportSubmissions;

use App\Filament\Resources\ReportSubmissions\Pages\ListReportSubmissions;
use App\Filament\Resources\ReportSubmissions\Pages\ViewReportSubmission;
use App\Filament\Resources\ReportSubmissions\Schemas\ReportSubmissionForm;
use App\Filament\Resources\ReportSubmissions\Tables\ReportSubmissionsTable;
use App\Models\ReportSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReportSubmissionResource extends Resource
{
    protected static ?string $model = ReportSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|\UnitEnum|null $navigationGroup = 'Reporting & Kunjungan';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Data Laporan Masuk';
    protected static ?string $modelLabel = 'Laporan Masuk';
    protected static ?string $pluralModelLabel = 'Data Laporan Masuk';

    protected static ?string $recordTitleAttribute = 'submission_code';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return ReportSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportSubmissionsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['template', 'principal', 'employee', 'values']);
        return \App\Traits\ScopesUserData::applyUserAccessScope($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportSubmissions::route('/'),
            'view' => ViewReportSubmission::route('/{record}'),
        ];
    }
}
