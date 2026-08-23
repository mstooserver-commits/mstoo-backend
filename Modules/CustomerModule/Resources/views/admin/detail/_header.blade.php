@php($fullName = trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')))
<div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <img class="rounded-circle" width="72" height="72"
             src="{{asset('storage/app/public/user/profile_image')}}/{{$customer->profile_image}}"
             onerror="this.src='{{asset('assets/admin-module')}}/img/media/upload-file.png'" alt="">
        <div>
            <h2 class="page-title mb-1">{{ $fullName ?: translate('customer_details') }}</h2>
            <div class="text-muted small">{{ $customer->email }} · {{ $customer->phone }}</div>
            <div class="mt-1">
                <span class="badge bg-{{$customer->is_active?'success':'secondary'}}">{{ $customer->is_active ? translate('active') : translate('inactive') }}</span>
                <span class="text-muted small ms-2">{{translate('joined')}}: {{ optional($customer->created_at)->format('d M Y') }}</span>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        @if(access_checker('customer_management', 'edit'))
            <a href="{{route('admin.customer.edit', [$customer->id])}}" class="btn btn--primary">
                <span class="material-icons">edit</span> {{translate('edit')}}
            </a>
        @endif
        <a href="{{route('admin.customer.index')}}" class="btn btn--secondary">{{translate('back')}}</a>
    </div>
</div>

<ul class="nav nav--tabs nav--tabs__style2 mb-3 flex-wrap">
    @php($tabs = [
        'overview' => 'overview',
        'bookings' => 'bookings',
        'transactions' => 'transactions',
        'wallet' => 'wallet',
        'loyalty' => 'loyalty_points',
        'membership' => 'pro_membership',
        'addresses' => 'addresses',
        'reviews' => 'reviews',
        'notifications' => 'notifications',
    ])
    @foreach($tabs as $key=>$label)
        <li class="nav-item">
            <a class="nav-link {{($web_page??'overview')==$key?'active':''}}"
               href="{{route('admin.customer.detail', [$customer->id, 'web_page'=>$key])}}">{{translate($label)}}</a>
        </li>
    @endforeach
</ul>
