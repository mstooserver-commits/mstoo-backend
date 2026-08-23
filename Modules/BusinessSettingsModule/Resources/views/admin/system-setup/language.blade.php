@extends('adminmodule::layouts.master')

@section('title', translate('language_setup'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{ translate('system_setup') }}</h2>
                <p class="text-muted mb-0">{{ translate('manage_supported_languages_without_deleting_translations') }}</p>
            </div>

            @include('businesssettingsmodule::admin.system-setup._nav')

            <form method="POST" action="{{ route('admin.system-setup.language.save') }}">
                @csrf
                @method('PUT')
                <div class="card mstoo-notify-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="mb-0">{{ translate('languages') }}</h4>
                        <small class="text-muted">{{ translate('disabling_a_language_hides_it_but_keeps_translations') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="search" class="form-control" id="language-search" placeholder="{{ translate('search') }}">
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle" id="language-table">
                                <thead>
                                <tr>
                                    <th>{{ translate('status') }}</th>
                                    <th>{{ translate('language') }}</th>
                                    <th>{{ translate('code') }}</th>
                                    <th>{{ translate('direction') }}</th>
                                    <th>{{ translate('default_language') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($catalog as $language)
                                    @php
                                        $code = $language['code'];
                                        $isEnabled = in_array($code, $enabled, true);
                                        $rtl = array_key_exists($code, $rtlOverrides)
                                            ? setting_flag($rtlOverrides[$code])
                                            : in_array($code, $rtlDefault, true);
                                    @endphp
                                    <tr class="language-row" data-search="{{ strtolower($language['name'].' '.$language['nativeName'].' '.$code) }}">
                                        <td>
                                            <label class="switcher mb-0">
                                                <input class="switcher_input language-enabled" type="checkbox" name="language_code[]" value="{{ $code }}" {{ $isEnabled ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $language['name'] }}</div>
                                            <small class="text-muted">{{ $language['nativeName'] }}</small>
                                        </td>
                                        <td><code>{{ $code }}</code></td>
                                        <td>
                                            <input type="hidden" name="language_rtl[{{ $code }}]" value="0">
                                            <label class="switcher mb-0">
                                                <input class="switcher_input" type="checkbox" name="language_rtl[{{ $code }}]" value="1" {{ $rtl ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                                <span class="switcher_control"></span>
                                            </label>
                                            <small class="text-muted">RTL</small>
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="default_language_code" value="{{ $code }}" {{ $default === $code ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @if($can_edit)
                    <div class="d-flex justify-content-end gap-2 mb-4">
                        <button type="reset" class="btn btn--secondary">{{ translate('reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('save_information') }}</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $('#language-search').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('.language-row').each(function () {
                $(this).toggle($(this).data('search').indexOf(q) !== -1);
            });
        });
    </script>
@endpush
