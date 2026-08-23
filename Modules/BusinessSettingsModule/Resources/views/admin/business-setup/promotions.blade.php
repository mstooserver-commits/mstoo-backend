@php
    $types = [
        'discount' => ['key' => 'discount_cost_bearer', 'title' => 'Normal_Discount'],
        'campaign' => ['key' => 'campaign_cost_bearer', 'title' => 'Campaign_Discount'],
        'coupon' => ['key' => 'coupon_cost_bearer', 'title' => 'Coupon_Discount'],
    ];
@endphp
<div class="row">
    @foreach($types as $type => $meta)
        @php($data = setting_live($data_values, $meta['key'], ['bearer' => 'admin', 'admin_percentage' => 100, 'provider_percentage' => 0]))
        <div class="col-lg-6 mb-3">
            <div class="card mstoo-notify-card h-100">
                <div class="card-header"><h4 class="mb-0">{{ translate($meta['title']) }}</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.business-settings.set-promotion-setup') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="type" value="{{ $type }}">
                        <p class="text-muted">{{ translate('Discount_Cost_Bearer') }}</p>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            @foreach(['admin','provider','both'] as $bearer)
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="bearer" value="{{ $bearer }}" {{ ($data['bearer'] ?? '') == $bearer ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                    {{ translate(ucfirst($bearer)) }}
                                </label>
                            @endforeach
                        </div>
                        <div class="row g-3 {{ ($data['bearer'] ?? '') == 'both' ? '' : 'd-none' }}" id="bearer-section__{{ $type }}">
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('Admin_Percentage') }} (%)</label>
                                <input type="number" min="0" max="100" step="any" name="admin_percentage" id="admin_percentage__{{ $type }}" class="form-control" value="{{ $data['admin_percentage'] ?? 0 }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('Provider_Percentage') }} (%)</label>
                                <input type="number" min="0" max="100" step="any" name="provider_percentage" id="provider_percentage__{{ $type }}" class="form-control" value="{{ $data['provider_percentage'] ?? 0 }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                        </div>
                        @if($can_edit)
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn--primary" type="submit">{{ translate('save_information') }}</button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
