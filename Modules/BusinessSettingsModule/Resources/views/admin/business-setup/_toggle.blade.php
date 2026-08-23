@php
    $checked = setting_flag(setting_live($data_values, $key, 0));
@endphp
<div class="col-md-6">
    <div class="border rounded p-3 h-100 d-flex justify-content-between align-items-start gap-3">
        <div>
            <div class="fw-semibold text-capitalize">{{ translate($label ?? $key) }}</div>
            @if(!empty($info))
                <small class="text-muted">{{ translate($info) }}</small>
            @endif
        </div>
        <label class="switcher mb-0">
            <input class="switcher_input" id="{{ $key }}__switch" type="checkbox"
                   {{ $checked ? 'checked' : '' }}
                   {{ empty($can_edit) ? 'disabled' : '' }}
                   @if(!empty($can_edit))
                   onclick="update_action_status('{{ $key }}', $(this).is(':checked') ? 1 : 0, '{{ $type }}')"
                   @endif>
            <span class="switcher_control"></span>
        </label>
    </div>
</div>
