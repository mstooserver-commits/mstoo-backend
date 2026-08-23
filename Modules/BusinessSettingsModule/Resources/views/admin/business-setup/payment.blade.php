<div class="row">
    @forelse($payment_values as $gateway)
        @php($values = $gateway->live_values ?? [])
        <div class="col-12 col-lg-6 mb-3">
            <div class="card mstoo-notify-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-capitalize">{{ translate($gateway->key_name) }}</h4>
                    <span class="badge {{ setting_flag($values['status'] ?? 0) ? 'bg-success' : 'bg-secondary' }}">
                        {{ setting_flag($values['status'] ?? 0) ? translate('active') : translate('inactive') }}
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.configuration.payment-set') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="gateway" value="{{ $gateway->key_name }}">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('status') }}</label>
                            <select name="status" class="form-control" {{ $can_edit ? '' : 'disabled' }}>
                                <option value="1" {{ setting_flag($values['status'] ?? 0) ? 'selected' : '' }}>{{ translate('active') }}</option>
                                <option value="0" {{ setting_flag($values['status'] ?? 0) ? '' : 'selected' }}>{{ translate('inactive') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('mode') }}</label>
                            <select name="mode" class="form-control" {{ $can_edit ? '' : 'disabled' }}>
                                <option value="live" {{ ($values['mode'] ?? '') == 'live' ? 'selected' : '' }}>{{ translate('live') }}</option>
                                <option value="test" {{ ($values['mode'] ?? '') == 'test' ? 'selected' : '' }}>{{ translate('test') }}</option>
                            </select>
                        </div>
                        @foreach($values as $key => $value)
                            @if(!in_array($key, ['gateway', 'mode', 'status'], true))
                                <div class="mb-3">
                                    <label class="form-label text-capitalize">{{ translate($key) }}</label>
                                    <input type="text" name="{{ $key }}" class="form-control" value="{{ $value }}" autocomplete="off" {{ $can_edit ? '' : 'readonly' }}>
                                </div>
                            @endif
                        @endforeach
                        @if($can_edit)
                            <div class="d-flex justify-content-end gap-2">
                                @if(in_array($gateway->key_name, ['stripe', 'razor_pay', 'paystack', 'flutterwave'], true))
                                    <button form="test-{{ $gateway->key_name }}" class="btn btn--secondary" type="submit">{{ translate('test_connection') }}</button>
                                @endif
                                <button class="btn btn--primary" type="submit">{{ translate('save_information') }}</button>
                            </div>
                        @endif
                    </form>
                    @if($can_edit && in_array($gateway->key_name, ['stripe', 'razor_pay', 'paystack', 'flutterwave'], true))
                        <form id="test-{{ $gateway->key_name }}" method="POST" action="{{ route('admin.configuration.payment-test') }}" class="d-none">
                            @csrf
                            <input type="hidden" name="gateway" value="{{ $gateway->key_name }}">
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info mb-0">{{ translate('no_payment_gateways_configured') }}</div></div>
    @endforelse
</div>
