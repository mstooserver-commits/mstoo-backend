<div class="mstoo-empty">
    <span class="material-icons">{{ $icon ?? 'inbox' }}</span>
    <h4>{{ $title ?? translate('No_data_found') }}</h4>
    @if(!empty($text))
        <p>{{ $text }}</p>
    @endif
</div>
