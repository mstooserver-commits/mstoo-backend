@extends('adminmodule::layouts.master')

@section('title', translate('plan_setup'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('plan_setup')}}</h2>
                    <p class="text-muted mb-0">{{translate('create_and_manage_pro_membership_plans')}}</p>
                </div>
                <a href="{{route('admin.pro-member.plans.create')}}" class="btn btn--primary">
                    <span class="material-icons">add</span> {{translate('add_plan')}}
                </a>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-6">
                            <input type="search" name="search" value="{{$search}}" class="form-control" placeholder="{{translate('search_by_plan_name')}}">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="all" {{$status=='all'?'selected':''}}>{{translate('all')}}</option>
                                <option value="active" {{$status=='active'?'selected':''}}>{{translate('active')}}</option>
                                <option value="inactive" {{$status=='inactive'?'selected':''}}>{{translate('inactive')}}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn--primary w-100" type="submit">{{translate('search')}}</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('plan_name')}}</th>
                                <th>{{translate('price')}}</th>
                                <th>{{translate('duration')}}</th>
                                <th>{{translate('benefits')}}</th>
                                <th>{{translate('active_members')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('created_date')}}</th>
                                <th class="text-center">{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($plans as $plan)
                                <tr>
                                    <td>{{$plans->firstItem() + $loop->index}}</td>
                                    <td>
                                        <div class="fw-semibold">{{$plan->name}}</div>
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($plan->description, 60) }}</div>
                                    </td>
                                    <td>
                                        {{ with_currency_symbol($plan->payablePrice()) }}
                                        @if($plan->discounted_price && $plan->discounted_price < $plan->price)
                                            <div class="text-muted small"><s>{{ with_currency_symbol($plan->price) }}</s></div>
                                        @endif
                                    </td>
                                    <td>{{ $plan->duration_days }} {{translate('days')}}</td>
                                    <td>{{ implode(', ', $plan->benefits ?? []) ?: '-' }}</td>
                                    <td>{{ $plan->active_members_count }}</td>
                                    <td>
                                        <label class="switcher">
                                            <input class="switcher_input" type="checkbox" {{$plan->is_active?'checked':''}}
                                                   onclick="route_alert('{{route('admin.pro-member.plans.status',[$plan->id])}}','{{translate('want_to_update_status')}}')">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>{{ optional($plan->created_at)->format('d M Y') }}</td>
                                    <td>
                                        <div class="table-actions justify-content-center">
                                            <a href="{{route('admin.pro-member.plans.show',[$plan->id])}}" class="table-actions_edit"><span class="material-icons">visibility</span></a>
                                            <a href="{{route('admin.pro-member.plans.edit',[$plan->id])}}" class="table-actions_edit"><span class="material-icons">edit</span></a>
                                            <button type="button" class="table-actions_delete bg-transparent border-0 p-0" onclick="form_alert('delete-{{$plan->id}}','{{translate('want_to_delete_this')}}?')">
                                                <span class="material-icons">delete</span>
                                            </button>
                                            <form id="delete-{{$plan->id}}" class="d-none" method="POST" action="{{route('admin.pro-member.plans.delete',[$plan->id])}}">
                                                @csrf @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center py-5 text-muted">{{translate('no_plans_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $plans->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
