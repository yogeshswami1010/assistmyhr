@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-center">
        <div class="w-full max-w-2xl">
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 font-semibold text-lg">{{ __('Register') }}</div>

                <div class="p-6">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-4">
                            <div class="flex flex-col md:flex-row md:items-center">
                                <label for="name" class="md:w-1/3 md:text-right md:pr-4 mb-2 md:mb-0">{{ __('Name') }}</label>

                                <div class="md:w-2/3">
                                    <input id="name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent{{ $errors->has('name') ? ' border-red-500' : '' }}" name="name" value="{{ old('name') }}" required autofocus>

                                    @if ($errors->has('name'))
                                        <span class="text-red-600 text-sm mt-1 block">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col md:flex-row md:items-center">
                                <label for="email" class="md:w-1/3 md:text-right md:pr-4 mb-2 md:mb-0">{{ __('E-Mail Address') }}</label>

                                <div class="md:w-2/3">
                                    <input id="email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent{{ $errors->has('email') ? ' border-red-500' : '' }}" name="email" value="{{ old('email') }}" required>

                                    @if ($errors->has('email'))
                                        <span class="text-red-600 text-sm mt-1 block">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col md:flex-row md:items-center">
                                <label for="password" class="md:w-1/3 md:text-right md:pr-4 mb-2 md:mb-0">{{ __('Password') }}</label>

                                <div class="md:w-2/3">
                                    <input id="password" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent{{ $errors->has('password') ? ' border-red-500' : '' }}" name="password" required>

                                    @if ($errors->has('password'))
                                        <span class="text-red-600 text-sm mt-1 block">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex flex-col md:flex-row md:items-center">
                                <label for="password-confirm" class="md:w-1/3 md:text-right md:pr-4 mb-2 md:mb-0">{{ __('Confirm Password') }}</label>

                                <div class="md:w-2/3">
                                    <input id="password-confirm" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" name="password_confirmation" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <div class="flex justify-end md:justify-start md:ml-1/3">
                                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                        {{-- Google Registration --}}
<div class="relative my-5 flex items-center">
    <div class="flex-grow border-t border-gray-300"></div>

    <span class="mx-4 text-xs font-medium text-gray-500">
        {{ __('OR REGISTER WITH') }}
    </span>

    <div class="flex-grow border-t border-gray-300"></div>
</div>

<a href="{{ route('google.login') }}"
   class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2.5 font-semibold text-gray-700 no-underline transition hover:bg-gray-50">

    <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.45 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.76 7.18l7.73 6C44.42 37.97 46.98 31.84 46.98 24.55z"/>
        <path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 019.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19C.93 17.48 0 20.13 0 24c0 3.87.93 7.52 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6c-2.15 1.45-4.89 2.3-8.18 2.3-6.26 0-11.55-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
    </svg>

    <span>{{ __('Continue with Google') }}</span>
</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
