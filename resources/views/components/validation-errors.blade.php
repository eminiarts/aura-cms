@if (count($errors->all()))
    <div class="block">
        <div class="mt-8 form_errors">
            <strong
                class="block text-red-600">{{ __('Unfortunately, there were still the following validation errors:') }}</strong>
            <div class="text-red-600 prose">
                <ul>
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
