<section>
    <header>
        <h2 class="text-lg font-medium">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information, email address, and mobile number.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.update') }}"
        class="mt-6 space-y-6"
    >
        @csrf
        @method('patch')

        <div class="space-y-2">
            <x-form.label
                for="name"
                :value="__('Name')"
            />

            <x-form.input
                id="name"
                name="name"
                type="text"
                class="block w-full"
                :value="old('name', $user->name)"
                required
                autofocus
                autocomplete="name"
            />

            <x-form.error :messages="$errors->get('name')" />
        </div>

        <div class="space-y-2">
            <x-form.label
                for="email"
                :value="__('Email')"
            />

            <x-form.input
                id="email"
                name="email"
                type="email"
                class="block w-full"
                :value="old('email', $user->email)"
                required
                autocomplete="email"
            />

            <x-form.error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-300">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500  dark:text-gray-400 dark:hover:text-gray-200 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-2">
            <x-form.label
                for="phone"
                :value="__('Mobile Number')"
            />

            <x-form.input
                id="phone"
                name="phone"
                type="text"
                class="block w-full"
                :value="old('phone', $user->phone)"
                autocomplete="tel"
                maxlength="11"
                placeholder="09XXXXXXXXX"
            />

            <x-form.error :messages="$errors->get('phone')" />

            @if ($user->phone)
                @if ($user->phone_verified)
                    <p class="text-sm font-medium text-green-600">
                        {{ __('Phone status: Verified') }}
                    </p>
                @else
                    <p class="text-sm font-medium text-amber-600">
                        {{ __('Phone status: Unverified') }}
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Phone status: No number on file') }}
                </p>
            @endif

            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('If you change your phone number and save, it will need to be verified again.') }}
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-button>
                {{ __('Save') }}
            </x-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>

    @if ($user->phone && ! $user->phone_verified)
        <div class="mt-6 space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <form method="post" action="{{ route('profile.phone.send-otp') }}">
                    @csrf
                    <x-button type="submit">
                        {{ __('Send OTP / Verify Number') }}
                    </x-button>
                </form>

                @if (session('status') === 'phone-otp-sent')
                    <p class="text-sm font-medium text-green-600">
                        {{ __('OTP sent. Enter the code below.') }}
                    </p>
                @endif
            </div>

            <form method="post" action="{{ route('profile.phone.verify-otp') }}" class="space-y-3">
                @csrf

                <div class="space-y-2">
                    <x-form.label for="otp_code" :value="__('Enter OTP Code')" />
                    <x-form.input
                        id="otp_code"
                        name="otp_code"
                        type="text"
                        class="block w-full"
                        maxlength="6"
                        :value="old('otp_code')"
                        autocomplete="one-time-code"
                        placeholder="6-digit code"
                    />
                    <x-form.error :messages="$errors->get('otp_code')" />
                </div>

                <x-button type="submit">
                    {{ __('Confirm OTP') }}
                </x-button>
            </form>
        </div>
    @endif

    @if (session('status') === 'phone-verified')
        <p class="mt-4 text-sm font-medium text-green-600">
            {{ __('Your mobile number is now verified.') }}
        </p>
    @endif

    @if (session('status') === 'phone-already-verified')
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Your mobile number is already verified.') }}
        </p>
    @endif
</section>
