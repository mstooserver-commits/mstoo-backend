<div class="bulk-ad-row card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <strong class="bulk-ad-label">Ad</strong>
            <button type="button" class="btn btn-sm btn-outline-danger bulk-ad-remove" data-remove-row>
                <span class="material-icons" style="font-size:18px">delete</span>
                {{translate('remove')}}
            </button>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Customer for this ad</label>
                <select name="ads[{{$index}}][user_id]" class="form-select bulk-compact-select" size="1">
                    <option value="">Use the customer above</option>
                    @foreach($customers as $customer)
                        <option value="{{$customer->id}}">
                            {{trim($customer->first_name.' '.$customer->last_name) ?: $customer->email}}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Ad name *</label>
                <input type="text" name="ads[{{$index}}][name]" class="form-control" placeholder="Honda City for rent">
            </div>
            <div class="col-md-4">
                <label class="form-label">Sub category *</label>
                <select name="ads[{{$index}}][sub_category_id]" class="form-select bulk-compact-select" size="1">
                    <option value="">Select</option>
                    @foreach($subCategories as $category)
                        <option value="{{$category->id}}">
                            {{$category->parent_name ? $category->parent_name.' / ' : ''}}{{$category->name}}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="ads[{{$index}}][cat_name]" class="form-select bulk-compact-select" size="1">
                    <option value="">General</option>
                    <option value="vehicle">Vehicle</option>
                    <option value="property">Property</option>
                    <option value="equipment">Equipment</option>
                    <option value="service">Service</option>
                    <option value="furniture">Furniture</option>
                    <option value="electronic">Electronic</option>
                    <option value="cloth">Cloth</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Price *</label>
                <input type="number" step="0.01" min="0" name="ads[{{$index}}][price]" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Duration</label>
                <input type="text" name="ads[{{$index}}][rent_duration]" class="form-control" placeholder="per day" value="per day">
            </div>
            <div class="col-md-2">
                <label class="form-label">Location</label>
                <input type="text" name="ads[{{$index}}][location]" class="form-control">
            </div>
            <div class="col-md-12">
                <label class="form-label">Description *</label>
                <textarea name="ads[{{$index}}][description]" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cover image</label>
                <input type="file" name="ads[{{$index}}][cover_image]" class="form-control" accept="image/*">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact</label>
                <input type="text" name="ads[{{$index}}][contact_info]" class="form-control">
            </div>
            <div class="col-12">
                <div class="bulk-ad-promos">
                    <div>
                        <label class="form-label">{{translate('discount')}}</label>
                        <select name="ads[{{$index}}][discount_id]" class="form-select bulk-compact-select" size="1">
                            <option value="">{{translate('none')}}</option>
                            @foreach($discounts as $discount)
                                <option value="{{$discount->id}}">{{$discount->discount_title}}</option>
                            @endforeach
                        </select>
                        @if(access_checker('promotion_management', 'create'))
                            <a class="btn btn-sm btn--secondary mt-2" href="{{route('admin.discount.create')}}" target="_blank">
                                <span class="material-icons">add</span> {{translate('add_discount')}}
                            </a>
                        @endif
                    </div>
                    <div>
                        <label class="form-label">{{translate('coupon')}}</label>
                        <select name="ads[{{$index}}][coupon_id]" class="form-select bulk-compact-select" size="1">
                            <option value="">{{translate('none')}}</option>
                            @foreach($coupons as $coupon)
                                <option value="{{$coupon->id}}">{{$coupon->coupon_code}}</option>
                            @endforeach
                        </select>
                        @if(access_checker('promotion_management', 'create'))
                            <a class="btn btn-sm btn--secondary mt-2" href="{{route('admin.coupon.create')}}" target="_blank">
                                <span class="material-icons">add</span> {{translate('add_coupon')}}
                            </a>
                        @endif
                    </div>
                    <div>
                        <label class="form-label">{{translate('campaign')}}</label>
                        <select name="ads[{{$index}}][campaign_id]" class="form-select bulk-compact-select" size="1">
                            <option value="">{{translate('none')}}</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{$campaign->id}}">{{$campaign->campaign_name}}</option>
                            @endforeach
                        </select>
                        @if(access_checker('promotion_management', 'create'))
                            <a class="btn btn-sm btn--secondary mt-2" href="{{route('admin.campaign.create')}}" target="_blank">
                                <span class="material-icons">add</span> {{translate('add_campaign')}}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
