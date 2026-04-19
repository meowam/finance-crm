<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Reports\ManagerReportsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Reports extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Звіти';

    protected static ?string $title = 'Звіти';

    protected static string|UnitEnum|null $navigationGroup = 'Аналітика';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports';

    public string $preset_period = '1m';

    /**
     * @var array<string, mixed>
     */
    public ?array $filters = [];

    public function mount(): void
    {
        $this->filters = [
            'mode' => 'summary',
            'date_from' => null,
            'date_until' => null,
            'manager_id' => null,
            'client_source' => null,
            'policy_status' => null,
        ];

        $this->applyPreset('1m');

        /** @var User|null $user */
        $user = Auth::user();

        if ($user instanceof User && $user->isManager()) {
            $this->filters['mode'] = 'detail';
            $this->filters['manager_id'] = (string) $user->id;
        }

        $this->form->fill($this->filters);
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User
            && $user->is_active
            && in_array($user->role, ['admin', 'supervisor', 'manager'], true);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getHeading(): string
    {
        return 'Звіти';
    }

    public function getSubheading(): ?string
    {
        return 'Зведена аналітика по менеджерах та детальний звіт по окремому менеджеру';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ComponentsGrid::make(6)
                    ->schema([
                        Select::make('mode')
                            ->label('Тип звіту')
                            ->options([
                                'summary' => 'Зведений',
                                'detail' => 'Детальний',
                            ])
                            ->native(false)
                            ->disabled(fn (): bool => $this->isManagerUser())
                            ->live()
                            ->afterStateUpdated(function ($state): void {
                                if ($this->isManagerUser()) {
                                    $this->filters['mode'] = 'detail';
                                    $this->filters['manager_id'] = (string) Auth::id();
                                    $this->form->fill($this->filters);
                                }

                                if ($state === 'detail' && ! filled($this->filters['manager_id'] ?? null) && ! $this->isManagerUser()) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Оберіть менеджера')
                                        ->body('Для детального режиму потрібно обрати менеджера.')
                                        ->send();
                                }
                            }),

                        DatePicker::make('date_from')
                            ->label('Період від')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->format('Y-m-d')
                            ->closeOnDateSelection()
                            ->live()
                            ->afterStateUpdated(function (): void {
                                $this->preset_period = 'custom';
                            }),

                        DatePicker::make('date_until')
                            ->label('Період до')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->format('Y-m-d')
                            ->closeOnDateSelection()
                            ->live()
                            ->afterStateUpdated(function (): void {
                                $this->preset_period = 'custom';
                            }),

                        Select::make('manager_id')
                            ->label('Менеджер')
                            ->options($this->getManagerOptions())
                            ->placeholder('Усі менеджери')
                            ->native(false)
                            ->searchable()
                            ->disabled(fn (): bool => $this->isManagerUser())
                            ->live(),

                        Select::make('client_source')
                            ->label('Джерело клієнта')
                            ->options([
                                'office' => 'Офіс',
                                'online' => 'Онлайн',
                                'recommendation' => 'Рекомендація',
                            ])
                            ->placeholder('Усі джерела')
                            ->native(false)
                            ->live(),

                        Select::make('policy_status')
                            ->label('Статус полісу')
                            ->options([
                                'draft' => 'Чернетка',
                                'active' => 'Активний',
                                'completed' => 'Завершено',
                                'canceled' => 'Скасовано',
                            ])
                            ->placeholder('Усі статуси')
                            ->native(false)
                            ->live(),
                    ]),
            ])
            ->statePath('filters');
    }

    public function setPresetPeriod(string $period): void
    {
        $this->applyPreset($period);
        $this->form->fill($this->filters);
    }

    protected function applyPreset(string $period): void
    {
        $this->preset_period = $period;

        $today = now()->toDateString();

        match ($period) {
            '1m' => [
                $this->filters['date_from'] = now()->subMonth()->toDateString(),
                $this->filters['date_until'] = $today,
            ],
            '3m' => [
                $this->filters['date_from'] = now()->subMonths(3)->toDateString(),
                $this->filters['date_until'] = $today,
            ],
            '6m' => [
                $this->filters['date_from'] = now()->subMonths(6)->toDateString(),
                $this->filters['date_until'] = $today,
            ],
            '12m' => [
                $this->filters['date_from'] = now()->subYear()->toDateString(),
                $this->filters['date_until'] = $today,
            ],
            'all' => [
                $this->filters['date_from'] = '2000-01-01',
                $this->filters['date_until'] = $today,
            ],
            default => null,
        };
    }

    public function resetFilters(): void
    {
        $this->filters['client_source'] = null;
        $this->filters['policy_status'] = null;

        $this->applyPreset('1m');

        if ($this->isManagerUser()) {
            $this->filters['mode'] = 'detail';
            $this->filters['manager_id'] = (string) Auth::id();
        } else {
            $this->filters['mode'] = 'summary';
            $this->filters['manager_id'] = null;
        }

        $this->form->fill($this->filters);
    }

    public function getManagerOptions(): array
    {
        return $this->service()
            ->getManagersForFilter(Auth::user())
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return [
            'date_from' => $this->filters['date_from'] ?? null,
            'date_until' => $this->filters['date_until'] ?? null,
            'manager_id' => $this->filters['manager_id'] ?? null,
            'client_source' => $this->filters['client_source'] ?? null,
            'policy_status' => $this->filters['policy_status'] ?? null,
        ];
    }

    public function getMode(): string
    {
        return (string) ($this->filters['mode'] ?? 'summary');
    }

    public function getSummaryRows(): array
    {
        return $this->service()->getSummaryRows($this->getFilters(), Auth::user());
    }

    public function getSummaryTotals(): array
    {
        return $this->service()->getSummaryTotals($this->getSummaryRows());
    }

    public function getDetailData(): ?array
    {
        return $this->service()->getDetailData($this->getFilters(), Auth::user());
    }

    public function exportSummaryCsv(): StreamedResponse
    {
        $rows = $this->getSummaryRows();

        $headers = [
            'Менеджер',
            'Нові клієнти',
            'Потенційні',
            'Активні',
            'Оформлено полісів',
            'Активні поліси',
            'Сума оплат за полісами',
            'Сплачені оплати',
            'Заплановані оплати',
            'Прострочені оплати',
            'Страхові випадки',
            'Заявлена сума',
            'Виплачена сума',
        ];

        $dataRows = array_map(function (array $row): array {
            return [
                $row['manager_name'],
                $row['new_clients'],
                $row['lead_clients'],
                $row['active_clients'],
                $row['policies_total'],
                $row['policies_active'],
                $this->normalizeNumber($row['premium_sum']),
                $row['payments_paid'],
                $row['payments_scheduled'],
                $row['payments_overdue'],
                $row['claims_total'],
                $this->normalizeNumber($row['claims_amount_claimed']),
                $this->normalizeNumber($row['claims_amount_paid']),
            ];
        }, $rows);

        return $this->streamCsvDownload(
            $this->buildFileName('zvedenyi-zvit', 'csv'),
            $headers,
            $dataRows
        );
    }

    public function exportDetailCsv(): StreamedResponse|Response
    {
        $detail = $this->getDetailData();

        if (! $detail) {
            return $this->missingDetailManagerResponse();
        }

        $headers = [
            'Менеджер',
            'Нові клієнти',
            'Потенційні',
            'Активні',
            'Оформлено полісів',
            'Активні поліси',
            'Сума оплат за полісами',
            'Сплачені оплати',
            'Заплановані оплати',
            'Прострочені оплати',
            'Страхові випадки',
            'Заявлена сума',
            'Виплачена сума',
        ];

        $dataRows = [[
            $detail['manager_name'],
            $detail['new_clients'],
            $detail['lead_clients'],
            $detail['active_clients'],
            $detail['policies_total'],
            $detail['policies_active'],
            $this->normalizeNumber($detail['premium_sum']),
            $detail['payments_paid'],
            $detail['payments_scheduled'],
            $detail['payments_overdue'],
            $detail['claims_total'],
            $this->normalizeNumber($detail['claims_amount_claimed']),
            $this->normalizeNumber($detail['claims_amount_paid']),
        ]];

        return $this->streamCsvDownload(
            $this->buildFileName('detalnyi-zvit', 'csv'),
            $headers,
            $dataRows
        );
    }

    public function exportSummaryExcel(): StreamedResponse
    {
        $rows = $this->getSummaryRows();

        $headers = [
            'Менеджер',
            'Нові клієнти',
            'Потенційні',
            'Активні',
            'Оформлено полісів',
            'Активні поліси',
            'Сума оплат за полісами',
            'Сплачені оплати',
            'Заплановані оплати',
            'Прострочені оплати',
            'Страхові випадки',
            'Заявлена сума',
            'Виплачена сума',
        ];

        $dataRows = array_map(function (array $row): array {
            return [
                $row['manager_name'],
                $row['new_clients'],
                $row['lead_clients'],
                $row['active_clients'],
                $row['policies_total'],
                $row['policies_active'],
                $this->normalizeNumber($row['premium_sum']),
                $row['payments_paid'],
                $row['payments_scheduled'],
                $row['payments_overdue'],
                $row['claims_total'],
                $this->normalizeNumber($row['claims_amount_claimed']),
                $this->normalizeNumber($row['claims_amount_paid']),
            ];
        }, $rows);

        return $this->streamExcelXmlDownload(
            $this->buildFileName('zvedenyi-zvit', 'xml'),
            $headers,
            $dataRows,
            'Звіт'
        );
    }

    public function exportDetailExcel(): StreamedResponse|Response
    {
        $detail = $this->getDetailData();

        if (! $detail) {
            return $this->missingDetailManagerResponse();
        }

        $headers = [
            'Менеджер',
            'Нові клієнти',
            'Потенційні',
            'Активні',
            'Оформлено полісів',
            'Активні поліси',
            'Сума оплат за полісами',
            'Сплачені оплати',
            'Заплановані оплати',
            'Прострочені оплати',
            'Страхові випадки',
            'Заявлена сума',
            'Виплачена сума',
        ];

        $dataRows = [[
            $detail['manager_name'],
            $detail['new_clients'],
            $detail['lead_clients'],
            $detail['active_clients'],
            $detail['policies_total'],
            $detail['policies_active'],
            $this->normalizeNumber($detail['premium_sum']),
            $detail['payments_paid'],
            $detail['payments_scheduled'],
            $detail['payments_overdue'],
            $detail['claims_total'],
            $this->normalizeNumber($detail['claims_amount_claimed']),
            $this->normalizeNumber($detail['claims_amount_paid']),
        ]];

        return $this->streamExcelXmlDownload(
            $this->buildFileName('detalnyi-zvit', 'xml'),
            $headers,
            $dataRows,
            'Звіт'
        );
    }

    protected function missingDetailManagerResponse(): Response
    {
        Notification::make()
            ->warning()
            ->title('Оберіть менеджера')
            ->body('Для детального експорту потрібно обрати менеджера.')
            ->send();

        return response('', 204);
    }

    protected function streamCsvDownload(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function streamExcelXmlDownload(string $filename, array $headers, array $rows, string $sheetName = 'Звіт'): StreamedResponse
    {
        $xml = $this->buildExcelXml($headers, $rows, $sheetName);

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    protected function buildExcelXml(array $headers, array $rows, string $sheetName = 'Звіт'): string
    {
        $escape = fn ($value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $headerCells = '';
        foreach ($headers as $header) {
            $headerCells .= '<Cell><Data ss:Type="String">' . $escape($header) . '</Data></Cell>';
        }

        $bodyRows = '';
        foreach ($rows as $row) {
            $bodyRows .= '<Row>';

            foreach ($row as $cell) {
                $type = is_numeric($cell) ? 'Number' : 'String';
                $bodyRows .= '<Cell><Data ss:Type="' . $type . '">' . $escape($cell) . '</Data></Cell>';
            }

            $bodyRows .= '</Row>';
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook
    xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    <Worksheet ss:Name="{$escape($sheetName)}">
        <Table>
            <Row>{$headerCells}</Row>
            {$bodyRows}
        </Table>
    </Worksheet>
</Workbook>
XML;
    }

    protected function buildFileName(string $prefix, string $extension): string
    {
        $from = $this->filters['date_from'] ?: now()->subDays(30)->toDateString();
        $until = $this->filters['date_until'] ?: now()->toDateString();

        return "{$prefix}-{$from}-{$until}.{$extension}";
    }

    protected function normalizeNumber(float|int|string|null $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    protected function isManagerUser(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user instanceof User && $user->isManager();
    }

    protected function service(): ManagerReportsService
    {
        return app(ManagerReportsService::class);
    }
}