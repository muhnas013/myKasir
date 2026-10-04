<div>
    <div class="app-topbar">
        <h1>Laporan</h1>
    </div>

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
        @if ($history->isEmpty())
            <x-empty-state title="Belum ada transaksi pada periode ini." icon="bar-chart" />
        @else
            <div class="card">
                <div class="stat-grid">
                    <div class="stat-tile">
                        <span class="stat-tile__label">Omzet</span>
                        <span class="stat-tile__value">{{ \App\Support\Money::format($summary['omzet']) }}</span>
                    </div>
                    <div class="stat-tile">
                        <span class="stat-tile__label">Transaksi</span>
                        <span class="stat-tile__value">{{ $summary['transaksi'] }}</span>
                    </div>
                    <div class="stat-tile">
                        <span class="stat-tile__label">Rata-rata</span>
                        <span class="stat-tile__value">{{ \App\Support\Money::format($summary['rata_rata']) }}</span>
                    </div>
                    @if ($canViewAll)
                        <div class="stat-tile">
                            <span class="stat-tile__label">Total HPP</span>
                            <span class="stat-tile__value">{{ \App\Support\Money::format($summary['hpp']) }}</span>
                        </div>
                        <div class="stat-tile">
                            <span class="stat-tile__label">Keuntungan Bersih</span>
                            <span class="stat-tile__value">{{ \App\Support\Money::format($summary['keuntungan_bersih']) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card__title">Omzet per Jam</div>
                @php($maxJam = max(1, max($hourly)))
                <div class="hourly-chart">
                    @foreach ($hourly as $jam => $total)
                        <div class="hourly-chart__bar" style="height: {{ max(2, round($total / $maxJam * 100)) }}%" title="{{ str_pad($jam, 2, '0', STR_PAD_LEFT) }}.00 — {{ \App\Support\Money::format($total) }}"></div>
                    @endforeach
                </div>
                <div class="hourly-chart__labels">
                    @foreach ($hourly as $jam => $total)
                        <span>{{ $jam % 3 === 0 ? $jam : '' }}</span>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card__title">Produk Terlaris</div>
                @if ($topProducts->isEmpty())
                    <x-empty-state title="Belum ada produk terjual." icon="utensils" />
                @else
                    <x-table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $row)
                                <tr wire:key="top-{{ $row->product_name }}">
                                    <td>{{ $row->product_name }}</td>
                                    <td class="num">{{ (int) $row->qty }}</td>
                                    <td class="num">{{ \App\Support\Money::format((int) $row->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </div>

            <div class="card">
                <div class="card__title">Metode Bayar</div>
                <x-table>
                    <thead>
                        <tr>
                            <th>Metode</th>
                            <th>Transaksi</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentMethods as $row)
                            <tr wire:key="method-{{ $row->method->value }}">
                                <td>{{ $row->method->label() }}</td>
                                <td class="num">{{ (int) $row->transaksi }}</td>
                                <td class="num">{{ \App\Support\Money::format((int) $row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </x-table>
            </div>

            <div class="card">
                <div class="card__header">
                    <div class="card__title">Riwayat Transaksi</div>
                    @if ($canViewAll)
                        <a href="{{ route('reports.export', ['start' => $rangeStart, 'end' => $rangeEnd]) }}" class="btn btn--secondary">
                            <x-icon name="download" /> Unduh Excel
                        </a>
                    @endif
                </div>

                @if ($history->isEmpty())
                    <x-empty-state title="Belum ada riwayat pada periode ini." icon="inbox" />
                @else
                    <x-table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Kasir</th>
                                <th>Item</th>
                                <th>Metode</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $order)
                                <tr wire:key="history-{{ $order->id }}">
                                    <td class="num">{{ ($order->paid_at ?? $order->created_at)->format('d/m H.i') }}</td>
                                    <td>{{ $order->user->name }}</td>
                                    <td>{{ $order->items->map(fn ($item) => $item->qty.'x '.$item->product_name)->implode(', ') }}</td>
                                    <td>{{ $order->payment?->method?->label() ?? '-' }}</td>
                                    <td class="num">{{ \App\Support\Money::format($order->total) }}</td>
                                    <td>
                                        @if ($order->status->value === 'paid')
                                            <x-badge variant="success">Lunas</x-badge>
                                        @else
                                            <x-badge variant="danger">Void</x-badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </div>
        @endif
    </div>
</div>
