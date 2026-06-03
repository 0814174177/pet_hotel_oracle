@props(['field' => null])

@if ($field)
    @error($field)
        <span {{ $attributes->merge(['class' => 'form-error']) }}>{{ $message }}</span>
    @enderror
@elseif ($errors->any())
    <span {{ $attributes->merge(['class' => 'form-error']) }}>{{ $errors->first() }}</span>
@endif
