<div class="bulk-ad-row border rounded p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <strong>Ad</strong>
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>Remove</button>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Customer for this ad</label>
            <select name="ads[{{$index}}][user_id]" class="form-select">
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
            <select name="ads[{{$index}}][sub_category_id]" class="form-select">
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
            <select name="ads[{{$index}}][cat_name]" class="form-select">
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
    </div>
</div>
