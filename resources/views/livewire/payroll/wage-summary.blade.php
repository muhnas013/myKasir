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
        @if (! $periodChosen)
            <x-empty-state title="Pilih periode dulu untuk melihat Rekap Upah." icon="wallet" />
        @elseif ($rows->isEmpty())
            <x-empty-state title="Belum ada shift selesai pada periode ini." icon="wallet" />
        @else
            <div class="card">
                <div class="card__header">
                    <div class="card__title">Total per Karyawan</div>
                    <a href="{{ route('payroll.export', ['start' => $rangeStart, 'end' => $rangeEnd]) }}" class="btn btn--secondary">
                        <x-icon name="download" /> Unduh Excel
                    </a>
                </div>
                <x-table>
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Omzet</th>
                            <th>Upah Dasar</th>
                            <th>Bonus Aktivitas</th>
                            <th>Bonus Penjualan</th>
                            <th>Total Upah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary as $s)
                            <tr wire:key="wage-{{ $s['name'] }}">
                                <td>{{ $s['name'] }}</td>
                                <td class="num">{{ \App\Support\Money::format($s['omzet']) }}</td>
                                <td class="num">{{ \App\Support\Money::format($s['base']) }}</td>
                                <td class="num">{{ \App\Support\Money::format($s['activity_total']) }}</td>
                                <td class="num">{{ \App\Support\Money::format($s['sales_bonus']) }}</td>
                                <td class="num"><strong>{{ \App\Support\Money::format($s['total']) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>
        @endif
    </div>
</div>
