@php($bonus = $bonus ?? null)
<div class="row">
    <div class="col-md-6 mb-3">
        <div class="form-floating">
            <input class="form-control" name="bonus_title" required value="{{old('bonus_title', $bonus->bonus_title ?? '')}}">
            <label>{{translate('title')}} *</label>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-floating">
            <select class="form-select" name="bonus_amount_type">
                <option value="amount" {{ old('bonus_amount_type', $bonus->bonus_amount_type ?? '') === 'amount' ? 'selected' : '' }}>{{translate('amount')}}</option>
                <option value="percent" {{ old('bonus_amount_type', $bonus->bonus_amount_type ?? '') === 'percent' ? 'selected' : '' }}>{{translate('percent')}}</option>
            </select>
            <label>{{translate('bonus_type')}}</label>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="form-floating">
            <input class="form-control" type="number" step="0.01" min="0" name="bonus_amount" required value="{{old('bonus_amount', $bonus->bonus_amount ?? 0)}}">
            <label>{{translate('bonus_amount')}} *</label>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="form-floating">
            <input class="form-control" type="number" step="0.01" min="0" name="min_add_money_amount" required value="{{old('min_add_money_amount', $bonus->min_add_money_amount ?? 0)}}">
            <label>{{translate('min_add_money_amount')}} *</label>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="form-floating">
            <input class="form-control" type="number" step="0.01" min="0" name="max_bonus_amount" required value="{{old('max_bonus_amount', $bonus->max_bonus_amount ?? 0)}}">
            <label>{{translate('maximum_bonus')}} *</label>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="form-floating">
            <input class="form-control" type="number" min="0" name="usage_limit" value="{{old('usage_limit', $bonus->usage_limit ?? 0)}}">
            <label>{{translate('usage_limit')}}</label>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="form-floating">
            <input class="form-control" type="number" min="0" name="per_user_limit" value="{{old('per_user_limit', $bonus->per_user_limit ?? 1)}}">
            <label>{{translate('limit_per_user')}}</label>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="form-floating">
            <input class="form-control" type="date" name="start_date" required value="{{ old('start_date', isset($bonus) && $bonus->start_date ? $bonus->start_date->format('Y-m-d') : '') }}">
            <label>{{translate('start_date')}} *</label>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="form-floating">
            <input class="form-control" type="date" name="end_date" required value="{{ old('end_date', isset($bonus) && $bonus->end_date ? $bonus->end_date->format('Y-m-d') : '') }}">
            <label>{{translate('end_date')}} *</label>
        </div>
    </div>
    <div class="col-12 mb-3">
        <div class="form-floating">
            <textarea class="form-control" name="description" style="height:90px">{{old('description', $bonus->description ?? '')}}</textarea>
            <label>{{translate('description')}}</label>
        </div>
    </div>
</div>
