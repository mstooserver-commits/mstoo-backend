@extends('adminmodule::layouts.master')

@section('title', translate('business_setup'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{ translate('business_setup') }}</h2>
                <p class="text-muted mb-0">{{ translate('configure_mstoo_business_and_system_settings') }}</p>
            </div>

            @include('businesssettingsmodule::admin.business-setup._tabs')

            @include('businesssettingsmodule::admin.business-setup.' . str_replace('_', '-', $web_page))
        </div>
    </div>
@endsection

@push('script')
    <script>
        function update_action_status(key_name, value, settings_type) {
            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: '{{ translate('want_to_update_status') }}',
                type: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                cancelButtonColor: 'var(--c2)',
                confirmButtonColor: 'var(--c1)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
                    });
                    $.ajax({
                        url: "{{ route('admin.business-settings.update-action-status') }}",
                        data: {key: key_name, value: value, settings_type: settings_type},
                        type: 'put',
                        success: function () {
                            toastr.success('{{ translate('successfully_updated') }}');
                        },
                        error: function () {
                            toastr.error('{{ translate('your access has been denied') }}');
                            document.location.reload();
                        }
                    });
                } else {
                    document.location.reload();
                }
            });
        }

        $(function () {
            $("input[name='bearer']").on('change', function () {
                var form = $(this).closest('form');
                var section = form.find("[id^='bearer-section__']");
                if ($(this).val() === 'both') {
                    section.removeClass('d-none');
                } else {
                    section.addClass('d-none');
                }
            });
        });
    </script>
@endpush
