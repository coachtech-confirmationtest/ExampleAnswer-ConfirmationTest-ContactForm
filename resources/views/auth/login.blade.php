<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center py-6 px-4">
        <div class="w-full max-w-md">
            <h1 class="text-3xl font-serif text-amber-900 text-center mb-6">{{ __('Login') }}</h1>
            <div class="bg-white rounded-lg shadow-sm">
                <form method="POST" action="{{ route('login') }}" class="px-8 py-8">
                    @csrf
                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block font-medium text-sm text-amber-900">
                            {{ __('メールアドレス') }}
                        </label>
                        <input id="email"
                            class="block mt-1 w-full border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm px-3 py-2"
                            type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" required
                            autofocus />
                        @if ($errors->get('email'))
                            <ul class="text-sm text-red-600 space-y-1 mt-2">
                                @foreach ((array) $errors->get('email') as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <!-- Password -->
                    <div class="mt-4">
                        <label for="password" class="block font-medium text-sm text-amber-900">
                            {{ __('パスワード') }}
                        </label>
                        <input id="password"
                            class="block mt-1 w-full border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm px-3 py-2"
                            type="password" name="password" placeholder="password" required />
                        @if ($errors->get('password'))
                            <ul class="text-sm text-red-600 space-y-1 mt-2">
                                @foreach ((array) $errors->get('password') as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="flex items-center justify-center mt-6">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-2 bg-[#82746a] hover:bg-[#6b5f57] focus:bg-[#6b5f57] active:bg-[#5a4f47] border border-transparent rounded-md font-semibold text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#82746a] focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25">
                            {{ __('ログイン') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
