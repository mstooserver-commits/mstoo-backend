@php
    $assigned = $assigned ?? [];
    $grantable = $grantable ?? [];
    $locked = $locked ?? [];
    $inputName = $inputName ?? 'permissions[]';
@endphp

<div class="mstoo-perm-list">
    @foreach($catalog as $moduleKey => $module)
        @php
            $moduleAssigned = 0;
            $moduleTotal = count($module['actions']);
            foreach ($module['actions'] as $action => $label) {
                if (in_array($moduleKey.'.'.$action, $assigned, true) || in_array($moduleKey.'.'.$action, $locked, true)) {
                    $moduleAssigned++;
                }
            }
        @endphp
        <div class="mstoo-perm-module is-open" data-module-name="{{$module['label']}} {{$moduleKey}}">
            <div class="mstoo-perm-head">
                <button type="button" class="mstoo-perm-toggle">
                    <span class="material-icons mstoo-perm-caret">expand_more</span>
                    <span>
                        <strong>{{$module['label']}}</strong>
                        <small class="d-block text-muted">{{$module['description']}}</small>
                    </span>
                </button>
                <div class="mstoo-perm-meta">
                    <span class="mstoo-perm-count">{{$moduleAssigned}}/{{$moduleTotal}}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary mstoo-perm-select-all">{{translate('select_all')}}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary mstoo-perm-clear">{{translate('clear_all')}}</button>
                </div>
            </div>
            <div class="mstoo-perm-body">
                @foreach($module['actions'] as $action => $label)
                    @php
                        $key = $moduleKey.'.'.$action;
                        $isLocked = in_array($key, $locked, true);
                        $isGrantable = in_array($key, $grantable, true);
                        $isChecked = $isLocked || in_array($key, $assigned, true);
                    @endphp
                    <label class="mstoo-perm-item" data-permission-name="{{$label}} {{$module['label']}} {{$key}}">
                        <input type="checkbox"
                               class="mstoo-permission-box"
                               name="{{$isLocked ? '' : $inputName}}"
                               value="{{$key}}"
                               {{$isChecked ? 'checked' : ''}}
                               {{(!$isGrantable || $isLocked) ? 'disabled' : ''}}
                               data-locked="{{$isLocked ? '1' : '0'}}">
                        <span>{{$label}}</span>
                        @if(!$isGrantable)
                            <small class="text-muted">{{translate('not_assignable')}}</small>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
