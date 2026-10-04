<div>
    <div class="card">
        <x-segmented :options="['today' => 'Hari Ini', '7' => '7 Hari', '30' => '30 Hari', 'custom' => 'Rentang']" :current="$period" model="period" />

        @if ($period === 'custom')
            <div class="date-range-row">
                <x-input label="Dari" type="date" wire:model.live="start" />
                <x-input label="Sampai" type="date" wire:model.live="end" />
            </div>
        @endif
    </div>

    <div wire:loading.delay class="skeleton skeleton--block"></div>

    <div wire:loading.remove>
        @if ($rows->isEmpty())
            <x-empty-state title="Belum ada shift selesai pada periode ini." icon="wallet" />
        @else
            @if ($summary !== [])
                <div class="card">
                    <div class="card__title">Total per Pegawai</div>
                    <div class="stat-grid">
                        @foreach ($summary as $s)
                            <div class="stat-tile">
                                <span class="stat-tile__label">{{ $s['name'] }} · {{ $s['shifts'] }} shift</span>
                                <span class="stat-tile__value">{{ \App\Support\Money::format($s['total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card__title">Rincian per Shift</div>
                <x-table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pegawai</th>
                            <th>Omzet</th>
                            <th>Upah Dasar</th>
                            <th>Aktivitas</th>
                            <th>Bonus Penjualan</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="wage-{{ $row['shift_id'] }}">
                                <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->translatedFormat('d M Y') }}</td>
                                <td>{{ $row['user_name'] }}</td>
                                <td class="num">{{ \App\Support\Money::format($row['omzet']) }}</td>
                                <td class="num">{{ \App\Support\Money::format($row['base']) }}</td>
                                <td>
                                    @if ($row['activities']->isEmpty())
                                        <span class="muted">—</span>
                                    @else
                                        {{ $row['activities']->pluck('name')->implode(', ') }}
                                        <span class="muted">({{ \App\Support\Money::format($row['activity_total']) }})</span>
                                    @endif
                                </td>
                                <td class="num">{{ \App\Support\Money::format($row['sales_bonus']) }}</td>
                                <td class="num"><strong>{{ \App\Support\Money::format($row['total']) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>
        @endif
    </div>
</div>
