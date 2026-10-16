<form method="POST" class="ajax-form" id="request-otp-form">
    @csrf
    <div class="mb-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="w-full sm:w-5/6">
                <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">@lang('app.mobile')</label>
                <div class="flex gap-2">
                    <div class="w-1/4">
                        <select name="calling_code" id="calling_code" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white" data-live-search="true">
                            @foreach ($calling_codes as $code => $value)
                                <option value="{{ $value['dial_code'] }}"
                                @selected(old('calling_code', $user->calling_code ?: '+1') == $value['dial_code'])>{{ $value['dial_code'] . ' - ' . $value['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1">
                        <input type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white px-3 py-2" id="mobile" name="mobile" value="{{ $user->mobile }}" autofocus />
                    </div>
                </div>
            </div>
            <div class="w-full sm:w-1/6 flex items-end">
                <button type="button" id="request-otp" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">@lang('app.requestOTP')</button>
            </div>
        </div>
    </div>
</form>