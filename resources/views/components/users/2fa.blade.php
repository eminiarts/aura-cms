@props(['field', 'model'])

<div class="p-8">

    2fa 

    {{-- @dump($model) --}}

    {{-- {{ $model->twoFactorQrCodeSvg() }} --}}

    {{-- {{ __('Two Factor Authentication') }}
    {{ __('Add additional security to your account using two factor authentication.') }}
    
    @if ($model->two_factor_secret)
        {{ __('You have enabled two factor authentication.') }}
    @else
        {{ __('You have not enabled two factor authentication.') }}
    @endif --}}

    {{-- livewire component --}}
    
</div>