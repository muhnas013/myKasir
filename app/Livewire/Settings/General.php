<?php

namespace App\Livewire\Settings;

use App\Actions\Settings\UpdateGeneralSettings;
use App\Services\SettingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class General extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:100')]
    public string $outlet_name = '';

    #[Validate('nullable|string|max:255')]
    public string $outlet_address = '';

    #[Validate('nullable|string|max:30')]
    public string $outlet_phone = '';

    #[Validate('boolean')]
    public bool $tax_enabled = false;

    #[Validate('required|numeric|min:0|max:100')]
    public float $tax_rate = 0;

    #[Validate('boolean')]
    public bool $service_enabled = false;

    #[Validate('required|numeric|min:0|max:100')]
    public float $service_rate = 0;

    #[Validate('required|integer|min:1')]
    public int $rounding_unit = 100;

    #[Validate('boolean')]
    public bool $allow_negative_stock = false;

    #[Validate('boolean')]
    public bool $method_cash = true;

    #[Validate('boolean')]
    public bool $method_qris = false;

    #[Validate('boolean')]
    public bool $method_card = false;

    #[Validate('nullable|string|max:500')]
    public string $receipt_footer = '';

    public $logo;

    public $qris_image;

    public ?string $logo_path = null;

    public ?string $qris_image_path = null;

    public function mount(SettingService $settings): void
    {
        $this->outlet_name = (string) $settings->get('outlet_name', '');
        $this->outlet_address = (string) $settings->get('outlet_address', '');
        $this->outlet_phone = (string) $settings->get('outlet_phone', '');
        $this->tax_enabled = (bool) $settings->get('tax_enabled', false);
        $this->tax_rate = ((int) $settings->get('tax_rate_bp', 0)) / 100;
        $this->service_enabled = (bool) $settings->get('service_enabled', false);
        $this->service_rate = ((int) $settings->get('service_rate_bp', 0)) / 100;
        $this->rounding_unit = (int) $settings->get('rounding_unit', 100);
        $this->allow_negative_stock = (bool) $settings->get('allow_negative_stock', false);
        $this->method_cash = (bool) $settings->get('method_cash', true);
        $this->method_qris = (bool) $settings->get('method_qris', false);
        $this->method_card = (bool) $settings->get('method_card', false);
        $this->receipt_footer = (string) $settings->get('receipt_footer', '');
        $this->logo_path = $settings->get('logo_path');
        $this->qris_image_path = $settings->get('qris_image_path');
    }

    public function save(UpdateGeneralSettings $action): void
    {
        $this->validate();

        if ($this->logo) {
            $this->validate(['logo' => 'image|mimes:jpg,jpeg,png,webp|max:2048']);
            $this->logo_path = $this->logo->store('settings', 'public');
        }

        if ($this->qris_image) {
            $this->validate(['qris_image' => 'image|mimes:jpg,jpeg,png,webp|max:2048']);
            $this->qris_image_path = $this->qris_image->store('settings', 'public');
        }

        $action->handle([
            'outlet_name' => $this->outlet_name,
            'outlet_address' => $this->outlet_address,
            'outlet_phone' => $this->outlet_phone,
            'tax_enabled' => $this->tax_enabled,
            'tax_rate_bp' => (int) round($this->tax_rate * 100),
            'service_enabled' => $this->service_enabled,
            'service_rate_bp' => (int) round($this->service_rate * 100),
            'rounding_unit' => $this->rounding_unit,
            'allow_negative_stock' => $this->allow_negative_stock,
            'method_cash' => $this->method_cash,
            'method_qris' => $this->method_qris,
            'method_card' => $this->method_card,
            'receipt_footer' => $this->receipt_footer,
            'logo_path' => $this->logo_path,
            'qris_image_path' => $this->qris_image_path,
        ], Auth::user());

        $this->logo = null;
        $this->qris_image = null;

        $this->dispatch('toast', type: 'success', message: 'Pengaturan disimpan.');
    }

    public function render()
    {
        return view('livewire.settings.general');
    }
}
