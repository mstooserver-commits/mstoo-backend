@extends('adminmodule::layouts.master')

@section('title', translate('ai_configuration'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{translate('ai_configuration')}}</h2>
                <p class="text-muted mb-0">{{translate('store_ai_provider_credentials_securely')}}</p>
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('admin.system-setup.ai.save') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="switcher">
                                <input class="switcher_input" type="checkbox" name="enabled" value="1" {{ !empty($values['enabled']) ? 'checked' : '' }}>
                                <span class="switcher_control"></span>
                                <span class="ms-2">{{translate('enable')}} AI</span>
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{translate('provider')}}</label>
                                <select name="provider" class="form-control">
                                    @foreach(['openai'=>'OpenAI','gemini'=>'Gemini','custom'=>'Custom'] as $key => $label)
                                        <option value="{{ $key }}" {{ ($values['provider'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{translate('model')}}</label>
                                <input class="form-control" name="model" value="{{ $values['model'] ?? 'gpt-4o-mini' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">API Key</label>
                                <input class="form-control" name="api_key" value="{{ $values['api_key_masked'] ?? '' }}" placeholder="sk-**************1234" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Base URL</label>
                                <input class="form-control" name="base_url" value="{{ $values['base_url'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Temperature</label>
                                <input type="number" step="0.1" min="0" max="2" class="form-control" name="temperature" value="{{ $values['temperature'] ?? 0.7 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max tokens</label>
                                <input type="number" class="form-control" name="max_tokens" value="{{ $values['max_tokens'] ?? 512 }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">System prompt</label>
                                <textarea class="form-control" name="system_prompt" rows="4">{{ $values['system_prompt'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <button class="btn btn--primary mt-4" type="submit">{{translate('save')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
