@php
    $isEdit = isset($role) && $role;
    $totalPermissions = count(all_permission_keys());
    $selectedCount = count($assigned);
@endphp

@extends('adminmodule::layouts.master')

@section('title', $isEdit ? translate('edit_role') : translate('add_new_role'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{ $isEdit ? translate('edit_role') : translate('add_new_role') }}</h2>
                    <p class="text-muted mb-0">{{translate('choose_a_role_name_then_select_module_permissions')}}</p>
                </div>
                <a href="{{route('admin.role.index')}}" class="btn btn--secondary">{{translate('back_to_roles')}}</a>
            </div>

            <form action="{{ $isEdit ? route('admin.role.update', [$role->id]) : route('admin.role.store') }}" method="POST" id="role-permission-form">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header">
                        <h4 class="mb-0">{{translate('role_information')}}</h4>
                    </div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-floating mb-30">
                                    <input type="text" class="form-control" name="role_name" value="{{old('role_name', $role->role_name ?? '')}}" placeholder="{{translate('role_name')}}" required {{($isEdit && $role->is_system && auth()->user()->user_type !== 'super-admin') ? 'readonly' : ''}}>
                                    <label>{{translate('role_name')}} *</label>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <label class="d-block mb-2 fw-medium">{{translate('status')}}</label>
                                <input type="hidden" name="is_active" value="0">
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="is_active" value="1"
                                           {{ old('is_active', $isEdit ? $role->is_active : 1) ? 'checked' : '' }}
                                           {{($isEdit && $role->is_system) ? 'disabled' : ''}}>
                                    <span class="switcher_control"></span>
                                </label>
                                @if($isEdit && $role->is_system)
                                    <div class="text-muted small mt-2">{{translate('system_roles_cannot_be_disabled')}}</div>
                                @endif
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" name="description" style="height: 100px" placeholder="{{translate('description')}}">{{old('description', $role->description ?? '')}}</textarea>
                                    <label>{{translate('description')}} ({{translate('optional')}})</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h4 class="mb-1">{{translate('permissions')}}</h4>
                            <div class="text-muted small" id="permission-counter">{{$selectedCount}} / {{$totalPermissions}} {{translate('permissions_selected')}}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn--secondary btn-sm" id="select-all-permissions">{{translate('select_all_permissions')}}</button>
                            <button type="button" class="btn btn--secondary btn-sm" id="clear-all-permissions">{{translate('clear_all_permissions')}}</button>
                        </div>
                    </div>
                    <div class="card-body p-30">
                        <div class="mb-4">
                            <input type="search" id="permission-search" class="form-control" placeholder="{{translate('search_permissions')}}">
                        </div>
                        @include('adminmodule::admin.employee.partials._permission-groups', [
                            'catalog' => $catalog,
                            'assigned' => old('permissions', $assigned),
                            'grantable' => $grantable,
                            'inputName' => 'permissions[]',
                            'locked' => [],
                        ])
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-3 mb-4">
                    <a href="{{route('admin.role.index')}}" class="btn btn--secondary">{{translate('cancel')}}</a>
                    <button type="submit" class="btn btn--primary">{{ $isEdit ? translate('save_role') : translate('save_role') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const search = document.getElementById('permission-search');
            const counter = document.getElementById('permission-counter');
            const total = {{$totalPermissions}};

            function updateCounter() {
                const selected = document.querySelectorAll('.mstoo-permission-box:not(:disabled):checked, .mstoo-permission-box[data-locked="1"]:checked').length
                    + document.querySelectorAll('.mstoo-permission-box:disabled:checked').length;
                const unique = document.querySelectorAll('.mstoo-permission-box:checked').length;
                counter.textContent = unique + ' / ' + total + ' {{translate('permissions_selected')}}';
                document.querySelectorAll('.mstoo-perm-module').forEach(function (module) {
                    const boxes = module.querySelectorAll('.mstoo-permission-box');
                    const checked = module.querySelectorAll('.mstoo-permission-box:checked');
                    const countEl = module.querySelector('.mstoo-perm-count');
                    if (countEl) {
                        countEl.textContent = checked.length + '/' + boxes.length;
                    }
                });
            }

            function filterPermissions() {
                const q = (search.value || '').toLowerCase().trim();
                document.querySelectorAll('.mstoo-perm-module').forEach(function (module) {
                    const moduleName = (module.getAttribute('data-module-name') || '').toLowerCase();
                    let any = moduleName.indexOf(q) !== -1;
                    module.querySelectorAll('.mstoo-perm-item').forEach(function (item) {
                        const label = (item.getAttribute('data-permission-name') || '').toLowerCase();
                        const match = !q || moduleName.indexOf(q) !== -1 || label.indexOf(q) !== -1;
                        item.classList.toggle('d-none', !match && moduleName.indexOf(q) === -1);
                        if (match) any = true;
                    });
                    module.classList.toggle('d-none', q !== '' && !any);
                    if (q && any) {
                        module.classList.add('is-open');
                    }
                });
            }

            document.querySelectorAll('.mstoo-perm-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.closest('.mstoo-perm-module').classList.toggle('is-open');
                });
            });

            document.querySelectorAll('.mstoo-perm-select-all').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.closest('.mstoo-perm-module').querySelectorAll('.mstoo-permission-box:not(:disabled)').forEach(function (box) {
                        box.checked = true;
                    });
                    updateCounter();
                });
            });

            document.querySelectorAll('.mstoo-perm-clear').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.closest('.mstoo-perm-module').querySelectorAll('.mstoo-permission-box:not(:disabled)').forEach(function (box) {
                        box.checked = false;
                    });
                    updateCounter();
                });
            });

            document.getElementById('select-all-permissions').addEventListener('click', function () {
                document.querySelectorAll('.mstoo-permission-box:not(:disabled)').forEach(function (box) {
                    box.checked = true;
                });
                updateCounter();
            });

            document.getElementById('clear-all-permissions').addEventListener('click', function () {
                document.querySelectorAll('.mstoo-permission-box:not(:disabled)').forEach(function (box) {
                    box.checked = false;
                });
                updateCounter();
            });

            document.querySelectorAll('.mstoo-permission-box').forEach(function (box) {
                box.addEventListener('change', updateCounter);
            });

            search.addEventListener('input', filterPermissions);
            updateCounter();
        })();
    </script>
@endpush
