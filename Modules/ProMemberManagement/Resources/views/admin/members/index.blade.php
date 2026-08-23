@extends('adminmodule::layouts.master')

@section('title', translate('pro_member_list'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{translate('pro_member_list')}}</h2>
                <p class="text-muted mb-0">{{translate('customers_with_current_or_previous_pro_membership')}}</p>
            </div>
            <div class="d-flex flex-wrap border-bottom mb-10 gap-3">
                @foreach(['all','active','expired','cancelled','pending'] as $tab)
                    <a class="nav-link {{$status==$tab?'active':''}}" href="{{route('admin.pro-member.members.index', array_merge(request()->except('status','page'), ['status'=>$tab]))}}">{{translate($tab)}}</a>
                @endforeach
            </div>
            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <input type="hidden" name="status" value="{{$status}}">
                        <div class="col-lg-4"><input class="form-control" type="search" name="search" value="{{$search}}" placeholder="{{translate('search_by_name_email_phone_or_id')}}"></div>
                        <div class="col-lg-2">
                            <select name="plan_id" class="form-control">
                                <option value="">{{translate('all_plans')}}</option>
                                @foreach($plans as $plan)
                                    <option value="{{$plan->id}}" {{$planId==$plan->id?'selected':''}}>{{$plan->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select name="date_type" class="form-control">
                                <option value="starts_at" {{$dateType=='starts_at'?'selected':''}}>{{translate('start_date')}}</option>
                                <option value="expires_at" {{$dateType=='expires_at'?'selected':''}}>{{translate('expiry_date')}}</option>
                                <option value="created_at" {{$dateType=='created_at'?'selected':''}}>{{translate('purchase_date')}}</option>
                            </select>
                        </div>
                        <div class="col-lg-1"><input type="date" class="form-control" name="from_date" value="{{$fromDate}}"></div>
                        <div class="col-lg-1"><input type="date" class="form-control" name="to_date" value="{{$toDate}}"></div>
                        <div class="col-lg-2"><button class="btn btn--primary w-100">{{translate('search')}}</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('customer')}}</th>
                                <th>{{translate('plan')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('start_date')}}</th>
                                <th>{{translate('expiry_date')}}</th>
                                <th>{{translate('amount_paid')}}</th>
                                <th>{{translate('auto_renewal')}}</th>
                                <th class="text-center">{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($members as $item)
                                <tr>
                                    <td>{{$members->firstItem() + $loop->index}}</td>
                                    <td>
                                        <div class="fw-semibold">{{ trim(($item->customer->first_name ?? '').' '.($item->customer->last_name ?? '')) ?: '-' }}</div>
                                        <div class="small">{{$item->customer->email ?? ''}}</div>
                                        <div class="small">{{$item->customer->phone ?? ''}}</div>
                                    </td>
                                    <td>{{$item->plan->name ?? '-'}}</td>
                                    <td><span class="badge bg-{{$item->status==='active'?'success':($item->status==='expired'?'secondary':'warning')}}">{{translate($item->status)}}</span></td>
                                    <td>{{optional($item->starts_at)->format('d M Y') ?: '-'}}</td>
                                    <td>{{optional($item->expires_at)->format('d M Y') ?: '-'}}</td>
                                    <td>{{with_currency_symbol($item->amount_paid)}}</td>
                                    <td>{{ $item->auto_renew ? translate('yes') : translate('no') }}</td>
                                    <td class="text-center">
                                        <a href="{{route('admin.pro-member.members.show',[$item->id])}}" class="table-actions_edit"><span class="material-icons">visibility</span></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center py-5 text-muted">{{translate('no_members_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $members->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
