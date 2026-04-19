<x-filament-panels::page>
    @php
        $summaryRows = $this->getSummaryRows();
        $summaryTotals = $this->getSummaryTotals();
        $detail = $this->getDetailData();
        $mode = $this->getMode();

        $money = fn ($value) => number_format((float) $value, 2, ',', ' ') . ' ₴';
        $int = fn ($value) => number_format((int) $value, 0, ',', ' ');
        $preset = $this->preset_period;
    @endphp

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --color-bg: #ffffff;
            --color-bg-secondary: #f5f5f3;
            --color-bg-tertiary: #efefec;
            --color-text-primary: #111110;
            --color-text-secondary: #6b6b67;
            --color-border-tertiary: rgba(0,0,0,0.10);
            --color-border-secondary: rgba(0,0,0,0.18);
            --border-radius-md: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --font-sans: system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        .dark {
            --color-bg: #1a1a18;
            --color-bg-secondary: #242422;
            --color-bg-tertiary: #2c2c2a;
            --color-text-primary: #f5f5f3;
            --color-text-secondary: #999994;
            --color-border-tertiary: rgba(255,255,255,0.09);
            --color-border-secondary: rgba(255,255,255,0.16);
        }

        .reports-dash {
            max-width: 1280px;
            margin: 0 auto;
            font-family: var(--font-sans);
            color: var(--color-text-primary);
        }

        .reports-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .reports-section-label {
            font-size: 11px;
            font-weight: 500;
            color: var(--color-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 10px;
        }

        .reports-period-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .reports-pill {
            font-size: 13px;
            padding: 5px 16px;
            border-radius: 20px;
            border: 0.5px solid var(--color-border-secondary);
            background: var(--color-bg);
            color: var(--color-text-secondary);
            cursor: pointer;
            transition: all 0.15s;
        }

        .reports-pill:hover {
            color: var(--color-text-primary);
            border-color: var(--color-border-secondary);
            background: var(--color-bg-secondary);
        }

        .reports-pill.active {
            background: #185fa5;
            border-color: #185fa5;
            color: #fff;
        }

        .reports-form-wrap {
            margin-bottom: 1.5rem;
        }

        .reports-form-wrap .fi-fo-field-wrp-label {
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 11px;
            font-weight: 500;
        }

        .reports-divider {
            border: none;
            border-top: 0.5px solid var(--color-border-tertiary);
            margin: 1.25rem 0;
        }

        .reports-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .reports-kpi-card {
            background: var(--color-bg-secondary);
            border-radius: var(--border-radius-lg);
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
        }

        .reports-kpi-label {
            font-size: 12px;
            color: var(--color-text-secondary);
            margin-bottom: 8px;
        }

        .reports-kpi-value {
            font-size: 22px;
            font-weight: 500;
            line-height: 1;
        }

        .reports-kpi-value.blue { color: #185fa5; }
        .reports-kpi-value.amber { color: #ba7517; }
        .reports-kpi-value.teal { color: #0f6e56; }
        .reports-kpi-value.small { font-size: 16px; margin-top: 4px; line-height: 1.3; }

        .reports-kpi-accent {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 28px;
            border-radius: 2px;
            background: var(--color-border-tertiary);
        }

        .reports-kpi-accent.blue { background: #378add; }
        .reports-kpi-accent.amber { background: #ef9f27; }
        .reports-kpi-accent.teal { background: #1d9e75; }

        .reports-table-wrap {
            border: 0.5px solid var(--color-border-tertiary);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            background: var(--color-bg);
        }

        .reports-table-header {
            padding: 13px 16px;
            border-bottom: 0.5px solid var(--color-border-tertiary);
            background: var(--color-bg);
        }

        .reports-table-header span {
            font-size: 14px;
            font-weight: 500;
        }

        .reports-table-scroll {
            overflow-x: auto;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 960px;
        }

        .reports-table thead th {
            padding: 9px 12px;
            background: var(--color-bg-secondary);
            color: var(--color-text-secondary);
            font-weight: 500;
            text-align: right;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
            border-bottom: 0.5px solid var(--color-border-tertiary);
        }

        .reports-table thead th:first-child {
            text-align: left;
        }

        .reports-table tbody tr {
            border-bottom: 0.5px solid var(--color-border-tertiary);
        }

        .reports-table tbody tr:last-child {
            border-bottom: none;
        }

        .reports-table tbody tr:hover td {
            background: var(--color-bg-secondary);
        }

        .reports-table tbody td {
            padding: 9px 12px;
            text-align: right;
            color: var(--color-text-primary);
            white-space: nowrap;
        }

        .reports-table tbody td:first-child {
            text-align: left;
            font-weight: 500;
        }

        .reports-table tfoot td {
            padding: 9px 12px;
            text-align: right;
            font-weight: 500;
            color: var(--color-text-primary);
            background: var(--color-bg-secondary);
            white-space: nowrap;
            border-top: 0.5px solid var(--color-border-secondary);
        }

        .reports-table tfoot td:first-child {
            text-align: left;
        }

        .reports-badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 500;
        }

        .reports-badge.paid {
            background: #eaf3de;
            color: #3b6d11;
        }

        .reports-badge.sched {
            background: #faeeda;
            color: #854f0b;
        }

        .reports-badge.overdue {
            background: #fcebeb;
            color: #a32d2d;
        }

        .reports-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .reports-empty {
            text-align: center;
            padding: 2rem;
            color: var(--color-text-secondary);
        }
    </style>

    <div class="reports-dash">
        <div class="reports-toolbar">
            @if ($mode === 'summary')
                <x-filament::button size="sm" color="gray" wire:click="exportSummaryCsv">
                    Експорт CSV
                </x-filament::button>

                <x-filament::button size="sm" color="gray" wire:click="exportSummaryExcel">
                    Експорт Excel
                </x-filament::button>
            @else
                <x-filament::button size="sm" color="gray" wire:click="exportDetailCsv">
                    Експорт CSV
                </x-filament::button>

                <x-filament::button size="sm" color="gray" wire:click="exportDetailExcel">
                    Експорт Excel
                </x-filament::button>
            @endif

            <x-filament::button size="sm" color="danger" wire:click="resetFilters">
                Скинути
            </x-filament::button>
        </div>

        <div class="reports-section-label">Швидкий період</div>
        <div class="reports-period-pills">
            <button type="button" class="reports-pill {{ $preset === '1m' ? 'active' : '' }}" wire:click="setPresetPeriod('1m')">1 місяць</button>
            <button type="button" class="reports-pill {{ $preset === '3m' ? 'active' : '' }}" wire:click="setPresetPeriod('3m')">3 місяці</button>
            <button type="button" class="reports-pill {{ $preset === '6m' ? 'active' : '' }}" wire:click="setPresetPeriod('6m')">6 місяців</button>
            <button type="button" class="reports-pill {{ $preset === '12m' ? 'active' : '' }}" wire:click="setPresetPeriod('12m')">12 місяців</button>
            <button type="button" class="reports-pill {{ $preset === 'all' ? 'active' : '' }}" wire:click="setPresetPeriod('all')">За весь час</button>
        </div>

        <div class="reports-form-wrap">
            {{ $this->form }}
        </div>

        <hr class="reports-divider">

        @if ($mode === 'summary')
            <div class="reports-kpi-grid">
                <div class="reports-kpi-card">
                    <div class="reports-kpi-label">Нові клієнти</div>
                    <div class="reports-kpi-value">{{ $int($summaryTotals['new_clients']) }}</div>
                    <div class="reports-kpi-accent"></div>
                </div>

                <div class="reports-kpi-card">
                    <div class="reports-kpi-label">Потенційні</div>
                    <div class="reports-kpi-value amber">{{ $int($summaryTotals['lead_clients']) }}</div>
                    <div class="reports-kpi-accent amber"></div>
                </div>

                <div class="reports-kpi-card">
                    <div class="reports-kpi-label">Активні</div>
                    <div class="reports-kpi-value teal">{{ $int($summaryTotals['active_clients']) }}</div>
                    <div class="reports-kpi-accent teal"></div>
                </div>

                <div class="reports-kpi-card">
                    <div class="reports-kpi-label">Поліси</div>
                    <div class="reports-kpi-value">{{ $int($summaryTotals['policies_total']) }}</div>
                    <div class="reports-kpi-accent"></div>
                </div>

                <div class="reports-kpi-card">
                    <div class="reports-kpi-label">Активні поліси</div>
                    <div class="reports-kpi-value blue">{{ $int($summaryTotals['policies_active']) }}</div>
                    <div class="reports-kpi-accent blue"></div>
                </div>

                <div class="reports-kpi-card">
                    <div class="reports-kpi-label">Сума оплат за полісами</div>
                    <div class="reports-kpi-value small">{{ $money($summaryTotals['premium_sum']) }}</div>
                    <div class="reports-kpi-accent"></div>
                </div>
            </div>

            <div class="reports-table-wrap">
                <div class="reports-table-header">
                    <span>Зведений звіт по менеджерах</span>
                </div>

                <div class="reports-table-scroll">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Менеджер</th>
                                <th>Нові</th>
                                <th>Потенційні</th>
                                <th>Активні</th>
                                <th>Поліси</th>
                                <th>Активні</th>
                                <th>Сума оплат</th>
                                <th>Сплачені</th>
                                <th>Заплановані</th>
                                <th>Прострочені</th>
                                <th>Випадки</th>
                                <th>Заявлено</th>
                                <th>Виплачено</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($summaryRows as $row)
                                <tr>
                                    <td>{{ $row['manager_name'] }}</td>
                                    <td>{{ $int($row['new_clients']) }}</td>
                                    <td>{{ $int($row['lead_clients']) }}</td>
                                    <td>{{ $int($row['active_clients']) }}</td>
                                    <td>{{ $int($row['policies_total']) }}</td>
                                    <td>{{ $int($row['policies_active']) }}</td>
                                    <td>{{ $money($row['premium_sum']) }}</td>
                                    <td><span class="reports-badge paid">{{ $int($row['payments_paid']) }}</span></td>
                                    <td><span class="reports-badge sched">{{ $int($row['payments_scheduled']) }}</span></td>
                                    <td><span class="reports-badge overdue">{{ $int($row['payments_overdue']) }}</span></td>
                                    <td>{{ $int($row['claims_total']) }}</td>
                                    <td>{{ $money($row['claims_amount_claimed']) }}</td>
                                    <td>{{ $money($row['claims_amount_paid']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="reports-empty">Немає даних за обраними фільтрами.</td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if (count($summaryRows))
                            <tfoot>
                                <tr>
                                    <td>Разом</td>
                                    <td>{{ $int($summaryTotals['new_clients']) }}</td>
                                    <td>{{ $int($summaryTotals['lead_clients']) }}</td>
                                    <td>{{ $int($summaryTotals['active_clients']) }}</td>
                                    <td>{{ $int($summaryTotals['policies_total']) }}</td>
                                    <td>{{ $int($summaryTotals['policies_active']) }}</td>
                                    <td>{{ $money($summaryTotals['premium_sum']) }}</td>
                                    <td><span class="reports-badge paid">{{ $int($summaryTotals['payments_paid']) }}</span></td>
                                    <td><span class="reports-badge sched">{{ $int($summaryTotals['payments_scheduled']) }}</span></td>
                                    <td><span class="reports-badge overdue">{{ $int($summaryTotals['payments_overdue']) }}</span></td>
                                    <td>{{ $int($summaryTotals['claims_total']) }}</td>
                                    <td>{{ $money($summaryTotals['claims_amount_claimed']) }}</td>
                                    <td>{{ $money($summaryTotals['claims_amount_paid']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @else
            @if (! $detail)
                <div class="reports-table-wrap">
                    <div class="reports-empty">Для детального звіту потрібно обрати менеджера.</div>
                </div>
            @else
                <div class="reports-detail-grid">
                    <div class="reports-kpi-card">
                        <div class="reports-kpi-label">Менеджер</div>
                        <div class="reports-kpi-value small">{{ $detail['manager_name'] }}</div>
                        <div class="reports-kpi-accent"></div>
                    </div>

                    <div class="reports-kpi-card">
                        <div class="reports-kpi-label">Нові клієнти</div>
                        <div class="reports-kpi-value">{{ $int($detail['new_clients']) }}</div>
                        <div class="reports-kpi-accent"></div>
                    </div>

                    <div class="reports-kpi-card">
                        <div class="reports-kpi-label">Потенційні / Активні</div>
                        <div class="reports-kpi-value small">{{ $int($detail['lead_clients']) }} / {{ $int($detail['active_clients']) }}</div>
                        <div class="reports-kpi-accent amber"></div>
                    </div>

                    <div class="reports-kpi-card">
                        <div class="reports-kpi-label">Поліси / Активні</div>
                        <div class="reports-kpi-value small">{{ $int($detail['policies_total']) }} / {{ $int($detail['policies_active']) }}</div>
                        <div class="reports-kpi-accent blue"></div>
                    </div>

                    <div class="reports-kpi-card">
                        <div class="reports-kpi-label">Сума оплат за полісами</div>
                        <div class="reports-kpi-value small">{{ $money($detail['premium_sum']) }}</div>
                        <div class="reports-kpi-accent"></div>
                    </div>

                    <div class="reports-kpi-card">
                        <div class="reports-kpi-label">Страхові випадки</div>
                        <div class="reports-kpi-value">{{ $int($detail['claims_total']) }}</div>
                        <div class="reports-kpi-accent teal"></div>
                    </div>
                </div>

                <div class="reports-table-wrap">
                    <div class="reports-table-header">
                        <span>Детальний звіт по менеджеру</span>
                    </div>

                    <div class="reports-table-scroll">
                        <table class="reports-table">
                            <tbody>
                                <tr>
                                    <td>Менеджер</td>
                                    <td>{{ $detail['manager_name'] }}</td>
                                </tr>
                                <tr>
                                    <td>Нові клієнти</td>
                                    <td>{{ $int($detail['new_clients']) }}</td>
                                </tr>
                                <tr>
                                    <td>Потенційні</td>
                                    <td>{{ $int($detail['lead_clients']) }}</td>
                                </tr>
                                <tr>
                                    <td>Активні</td>
                                    <td>{{ $int($detail['active_clients']) }}</td>
                                </tr>
                                <tr>
                                    <td>Оформлено полісів</td>
                                    <td>{{ $int($detail['policies_total']) }}</td>
                                </tr>
                                <tr>
                                    <td>Активні поліси</td>
                                    <td>{{ $int($detail['policies_active']) }}</td>
                                </tr>
                                <tr>
                                    <td>Сума оплат за полісами</td>
                                    <td>{{ $money($detail['premium_sum']) }}</td>
                                </tr>
                                <tr>
                                    <td>Сплачені оплати</td>
                                    <td>{{ $int($detail['payments_paid']) }}</td>
                                </tr>
                                <tr>
                                    <td>Заплановані оплати</td>
                                    <td>{{ $int($detail['payments_scheduled']) }}</td>
                                </tr>
                                <tr>
                                    <td>Прострочені оплати</td>
                                    <td>{{ $int($detail['payments_overdue']) }}</td>
                                </tr>
                                <tr>
                                    <td>Страхові випадки</td>
                                    <td>{{ $int($detail['claims_total']) }}</td>
                                </tr>
                                <tr>
                                    <td>Заявлена сума</td>
                                    <td>{{ $money($detail['claims_amount_claimed']) }}</td>
                                </tr>
                                <tr>
                                    <td>Виплачена сума</td>
                                    <td>{{ $money($detail['claims_amount_paid']) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>