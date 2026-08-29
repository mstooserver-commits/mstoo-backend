@php
    $ad = $service ?? null;
    $deliveryPickup = strtolower((string) optional($ad)->delivery_pickup);
@endphp
<div class="mstoo-ad-section">
    <h5>{{translate('product_photos')}}</h5>
    <p class="text-muted small">{{translate('first_image_is_used_as_cover')}}</p>
    <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('location')}}</h5>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">{{translate('address')}}</label>
            <input class="form-control" name="location" value="{{ old('location', optional($ad)->location) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{translate('latitude')}}</label>
            <input class="form-control" name="latitude" value="{{ old('latitude', optional($ad)->latitude) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{translate('longitude')}}</label>
            <input class="form-control" name="longitude" value="{{ old('longitude', optional($ad)->longitude) }}">
        </div>
    </div>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('availability_calendar')}}</h5>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">{{translate('availability')}}</label>
            <input class="form-control" name="availability" value="{{ old('availability', optional($ad)->availability) }}" placeholder="available / booked / blocked dates">
        </div>
        <div class="col-md-6">
            <label class="form-label">{{translate('availability_date')}}</label>
            <input type="date" class="form-control" name="availability_date" value="{{ old('availability_date', optional($ad)->availability_date) }}">
        </div>
    </div>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('contact_information')}}</h5>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">{{translate('contact_name')}}</label>
            <input class="form-control" name="contact_name" value="{{ old('contact_name') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{translate('phone')}}</label>
            <input class="form-control" name="contact_phone" value="{{ old('contact_phone') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{translate('email')}}</label>
            <input type="email" class="form-control" name="contact_email" value="{{ old('contact_email') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{translate('alternate_phone')}}</label>
            <input class="form-control" name="contact_alt_phone" value="{{ old('contact_alt_phone') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">{{translate('preferred_contact_method')}}</label>
            <select class="form-control" name="preferred_contact">
                <option value="phone">Phone</option>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{translate('contact_info')}}</label>
            <input class="form-control" name="contact_info" value="{{ old('contact_info', optional($ad)->contact_info) }}">
        </div>
    </div>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('deposit_and_security')}}</h5>
    <textarea class="form-control" name="deposits" rows="3">{{ old('deposits', optional($ad)->deposits) }}</textarea>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('documents_required')}}</h5>
    <textarea class="form-control" name="doc_required" rows="3" placeholder="ID Proof, Address Proof, License">{{ old('doc_required', optional($ad)->doc_required) }}</textarea>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('additional_information')}}</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Brand</label><input class="form-control" name="vehicle_brand" value="{{ old('vehicle_brand', optional($ad)->vehicle_brand) }}"></div>
        <div class="col-md-4"><label class="form-label">Model / Year</label><input class="form-control" name="model_year" value="{{ old('model_year', optional($ad)->model_year) }}"></div>
        <div class="col-md-4"><label class="form-label">Condition</label><input class="form-control" name="condition" value="{{ old('condition', optional($ad)->condition) }}"></div>
        <div class="col-12"><textarea class="form-control" name="additional_info" rows="3">{{ old('additional_info', optional($ad)->additional_info) }}</textarea></div>
    </div>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('delivery_pickup')}}</h5>
    <div class="d-flex gap-4 mb-3">
        <label><input type="checkbox" name="delivery_enabled" value="1" {{ str_contains($deliveryPickup, 'delivery') ? 'checked' : '' }}> {{translate('delivery')}}</label>
        <label><input type="checkbox" name="pickup_enabled" value="1" {{ str_contains($deliveryPickup, 'pickup') ? 'checked' : '' }}> {{translate('pickup')}}</label>
    </div>
    <input class="form-control" name="delivery_pickup_notes" placeholder="Delivery / pickup notes" value="{{ old('delivery_pickup_notes') }}">
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('safety_guidelines')}}</h5>
    <textarea class="form-control ckeditor" name="safety" rows="4">{{ old('safety', optional($ad)->safety) }}</textarea>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('terms_and_conditions')}}</h5>
    <textarea class="form-control ckeditor" name="t_and_c" rows="4">{{ old('t_and_c', optional($ad)->t_and_c) }}</textarea>
</div>

<div class="mstoo-ad-section">
    <h5>{{translate('featured_ad')}}</h5>
    <label class="switcher mb-0">
        <input class="switcher_input" type="checkbox" name="is_featured" value="1" {{ old('is_featured', optional($ad)->is_featured) == 'yes' ? 'checked' : '' }}>
        <span class="switcher_control"></span>
        <span class="ms-2">{{translate('yes')}} / {{translate('no')}}</span>
    </label>
</div>
