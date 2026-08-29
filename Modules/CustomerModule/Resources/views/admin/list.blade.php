@extends('adminmodule::layouts.master')

@section('title', translate('customer_list'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('customer_list')}}</h2>
                    <p class="text-muted mb-0">{{translate('view_and_manage_registered_customers')}}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(access_checker('promotion_management', 'create'))
                        <a href="{{route('admin.discount.create')}}" class="btn btn--secondary">
                            <span class="material-icons">local_offer</span>
                            {{translate('add_discount')}}
                        </a>
                        <a href="{{route('admin.coupon.create')}}" class="btn btn--secondary">
                            <span class="material-icons">confirmation_number</span>
                            {{translate('add_coupon')}}
                        </a>
                    @endif
                    @if(access_checker('customer_management', 'create'))
                        <a href="{{route('admin.customer.create')}}" class="btn btn--primary">
                            <span class="material-icons">add</span>
                            {{translate('add_customer')}}
                        </a>
                    @endif
                </div>
            </div>

            <div class="card mstoo-notify-card mb-3">
                <div class="card-header"><h4 class="mb-0">{{translate('search_filter')}}</h4></div>
                <div class="card-body">
                    <form method="GET" action="{{route('admin.customer.index')}}" class="row g-3">
                        <input type="hidden" name="status" value="{{$status}}">
                        <div class="col-md-3">
                            <label class="form-label">{{translate('start_date')}}</label>
                            <input type="date" name="from_date" value="{{$from_date}}" class="form-control" max="{{$to_date ?: ''}}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{translate('end_date')}}</label>
                            <input type="date" name="to_date" value="{{$to_date}}" class="form-control" min="{{$from_date ?: ''}}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{translate('sort_by')}}</label>
                            <select name="sort" class="form-control">
                                <option value="latest" {{$sort=='latest'?'selected':''}}>{{translate('latest')}}</option>
                                <option value="oldest" {{$sort=='oldest'?'selected':''}}>{{translate('oldest')}}</option>
                                <option value="name_az" {{$sort=='name_az'?'selected':''}}>{{translate('name_a_z')}}</option>
                                <option value="name_za" {{$sort=='name_za'?'selected':''}}>{{translate('name_z_a')}}</option>
                                <option value="bookings_desc" {{$sort=='bookings_desc'?'selected':''}}>{{translate('most_bookings')}}</option>
                                <option value="bookings_asc" {{$sort=='bookings_asc'?'selected':''}}>{{translate('least_bookings')}}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{translate('limit')}}</label>
                            <select name="limit" class="form-control">
                                @foreach([10,25,50,100] as $size)
                                    <option value="{{$size}}" {{(int)$limit===$size?'selected':''}}>{{$size}}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(!empty($hasDocument))
                            <div class="col-md-3">
                                <label class="form-label">{{translate('document_status')}}</label>
                                <select name="document" class="form-control">
                                    <option value="all" {{$document=='all'?'selected':''}}>{{translate('all')}}</option>
                                    <option value="pending" {{$document=='pending'?'selected':''}}>{{translate('pending')}}</option>
                                    <option value="approved" {{$document=='approved'?'selected':''}}>{{translate('approved')}}</option>
                                    <option value="rejected" {{$document=='rejected'?'selected':''}}>{{translate('rejected')}}</option>
                                </select>
                            </div>
                        @endif
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="{{route('admin.customer.index')}}" class="btn btn--secondary">{{translate('reset')}}</a>
                            <button class="btn btn--primary" type="submit">{{translate('filter')}}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mb-10 gap-3">
                <ul class="nav nav--tabs">
                    @foreach(['all','active','inactive'] as $tab)
                        <li class="nav-item">
                            <a class="nav-link {{$status==$tab?'active':''}}"
                               href="{{route('admin.customer.index', array_merge(request()->except('status','page'), ['status'=>$tab]))}}">
                                {{translate($tab)}}
                                <span class="badge bg-light text-dark ms-1">{{$counts[$tab] ?? 0}}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="d-flex gap-2 fw-medium">
                    <span class="opacity-75">{{translate('Total_Customers')}}:</span>
                    <span class="title-color">{{ number_format($counts['all'] ?? 0) }}</span>
                </div>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                        <form method="GET" action="{{route('admin.customer.index')}}" class="search-form search-form_style-two flex-grow-1" style="max-width:420px">
                            @foreach(request()->except('search','page') as $key=>$value)
                                @if(!is_array($value))
                                    <input type="hidden" name="{{$key}}" value="{{$value}}">
                                @endif
                            @endforeach
                            <div class="input-group search-form__input_group">
                                <span class="search-form__icon"><span class="material-icons">search</span></span>
                                <input type="search" class="theme-input-style search-form__input" name="search" value="{{$search}}" placeholder="{{translate('search_by_name_email_phone_or_id')}}">
                            </div>
                            <button class="btn btn--primary" type="submit">{{translate('search')}}</button>
                        </form>
                        @if(access_checker('customer_management', 'export'))
                            <a class="btn btn--secondary" href="{{route('admin.customer.download', request()->query())}}">
                                <span class="material-icons">file_download</span> {{translate('excel')}}
                            </a>
                        @endif
                    </div>

                    <form method="POST" action="{{route('admin.customer.bulk')}}" id="customer-bulk-form">
                        @csrf
                        @if(access_checker('customer_management', 'edit'))
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <select name="action" class="form-control" style="max-width:180px" required>
                                    <option value="">{{translate('bulk_action')}}</option>
                                    <option value="activate">{{translate('activate')}}</option>
                                    <option value="deactivate">{{translate('deactivate')}}</option>
                                    @if(access_checker('customer_management', 'delete'))
                                        <option value="delete">{{translate('delete')}}</option>
                                    @endif
                                </select>
                                <button class="btn btn--primary" type="submit" onclick="return confirm('{{translate('apply_this_action_to_selected_customers')}}?')">{{translate('apply')}}</button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    @if(access_checker('customer_management', 'edit'))
                                        <th style="width:36px"><input type="checkbox" id="select-all-customers"></th>
                                    @endif
                                    <th>{{translate('SL')}}</th>
                                    <th>{{translate('customer')}}</th>
                                    <th>{{translate('joined')}}</th>
                                    <th>{{translate('total_bookings')}}</th>
                                    <th>{{translate('status')}}</th>
                                    @if(!empty($hasDocument))
                                        <th>{{translate('document')}}</th>
                                    @endif
                                    <th class="text-center">{{translate('action')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($customers as $customer)
                                    <tr>
                                        @if(access_checker('customer_management', 'edit'))
                                            <td><input type="checkbox" name="customer_ids[]" value="{{$customer->id}}" class="customer-row-check"></td>
                                        @endif
                                        <td>{{$customers->firstItem() + $loop->index}}</td>
                                        <td>
                                            <a href="{{route('admin.customer.detail', [$customer->id, 'web_page'=>'overview'])}}" class="d-flex align-items-center gap-2 text-reset">
                                                <img class="rounded-circle" width="40" height="40"
                                                     src="{{asset('storage/app/public/user/profile_image')}}/{{$customer->profile_image}}"
                                                     onerror="this.src='{{asset('assets/admin-module')}}/img/media/upload-file.png'"
                                                     alt="">
                                                <div>
                                                    <div class="fw-semibold">{{ trim($customer->first_name.' '.$customer->last_name) ?: '-' }}</div>
                                                    <div class="text-muted small">{{ mask_phone($customer->phone) }}</div>
                                                    <div class="text-muted small">{{ mask_email($customer->email) }}</div>
                                                </div>
                                            </a>
                                        </td>
                                        <td>{{ optional($customer->created_at)->format('d M Y') }}</td>
                                        <td>{{ $customer->bookings_count }}</td>
                                        <td>
                                            <label class="switcher">
                                                <input class="switcher_input" type="checkbox" {{$customer->is_active?'checked':''}}
                                                       @if(!access_checker('customer_management','edit')) disabled
                                                       @else onclick="route_alert('{{route('admin.customer.status-update',[$customer->id])}}','{{translate('want_to_update_status')}}')"
                                                       @endif>
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>
                                        @if(!empty($hasDocument))
                                            <td>
                                                @if(($customer->document_status ?? null) === 'pending')
                                                    <span class="badge bg-warning text-dark">{{translate('pending')}}</span>
                                                @elseif(($customer->document_status ?? null) === 'approved')
                                                    <span class="mstoo-badge approved">{{translate('approved')}}</span>
                                                @elseif(($customer->document_status ?? null) === 'rejected')
                                                    <span class="mstoo-badge rejected">{{translate('rejected')}}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            <div class="table-actions justify-content-center">
                                                <a href="{{route('admin.customer.detail', [$customer->id, 'web_page'=>'overview'])}}" class="table-actions_view" title="{{translate('view')}}">
                                                    <span class="material-icons">visibility</span>
                                                </a>
                                                @if(access_checker('customer_management', 'edit'))
                                                    <a href="{{route('admin.customer.edit', [$customer->id])}}" class="table-actions_edit" title="{{translate('edit')}}">
                                                        <span class="material-icons">edit</span>
                                                    </a>
                                                @endif
                                                @if(access_checker('promotion_management', 'create'))
                                                    <a href="{{route('admin.discount.create', ['customer_id' => $customer->id])}}" class="table-actions_promo" title="{{translate('add_discount')}}">
                                                        <span class="material-icons">local_offer</span>
                                                    </a>
                                                    <a href="{{route('admin.coupon.create', ['customer_id' => $customer->id])}}" class="table-actions_promo" title="{{translate('add_coupon')}}">
                                                        <span class="material-icons">confirmation_number</span>
                                                    </a>
                                                @endif
                                                @if(access_checker('customer_management', 'delete'))
                                                    <button type="button" class="table-actions_delete bg-transparent border-0 p-0" onclick="form_alert('customer-delete-{{$customer->id}}','{{translate('want_to_delete_this_customer')}}?')">
                                                        <span class="material-icons">delete</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            @include('adminmodule::layouts.partials._empty', ['icon' => 'group_off', 'title' => translate('No_data_found'), 'text' => translate('customer_list')])
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                    @foreach($customers as $customer)
                        @if(access_checker('customer_management', 'delete'))
                            <form id="customer-delete-{{$customer->id}}" class="d-none" method="POST" action="{{route('admin.customer.delete',[$customer->id])}}">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    @endforeach
                    <div class="d-flex justify-content-end">{!! $customers->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.getElementById('select-all-customers')?.addEventListener('change', function () {
            document.querySelectorAll('.customer-row-check').forEach(function (el) {
                el.checked = this.checked;
            }, this);
        });
    </script>
@endpush
