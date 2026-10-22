@props(['name', 'label', 'type' => 'text', 'required' => false, 'options' => [], 'value' => '', 'hint' => null])
<div class="field">
    <label for="field-{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
    @if($type === 'select')
    <select id="field-{{ $name }}" name="{{ $name }}" {{ $attributes }} @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="error-{{ $name }}" @endif>
        <option value="">Pilih {{ mb_strtolower($label) }}</option>
        @foreach($options as $key => $text)<option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>@endforeach
    </select>
    @elseif($type === 'textarea')
    <textarea id="field-{{ $name }}" name="{{ $name }}" rows="5" {{ $attributes }} @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="error-{{ $name }}" @endif>{{ old($name, $value) }}</textarea>
    @else
    <input id="field-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes }} @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="error-{{ $name }}" @endif>
    @endif
    @if($hint)<small>{{ $hint }}</small>@endif
    @error($name)<p class="field-error" id="error-{{ $name }}">{{ $message }}</p>@enderror
</div>
