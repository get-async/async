<?php
/*
 * @var Collection $apiKeys
 * @var bool $has_2fa
 */
?>

<h2 class="font-semi-bold mb-1 text-lg">{{ __('Two-factor authentication') }}</h2>
<p class="mb-2 text-sm text-zinc-500">{{ __('Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to sign in.') }}</p>
<p class="mb-4 text-sm text-zinc-500">{{ __('Set your preferred method to use for two-factor authentication when signing into PeopleOS.') }}</p>

<form x-target="timezone-form" x-target.back="timezone-form" id="timezone-form" action="{{ route('settings.security.2fa.update') }}" method="post" class="mb-8 border border-gray-200 bg-white sm:rounded-lg" x-data="{ showActions: false }">
  @csrf
  @method('put')

  <!-- timezone -->
  <div class="grid grid-cols-3 items-center rounded-t-lg p-3 last:rounded-b-lg hover:bg-blue-50">
    <p class="col-span-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Preferred methods') }}</p>
    <div class="col-span-1 w-full justify-self-end">
      {{-- <select id="timezone" name="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 disabled:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600">
        <option value="{{ App\Enums\TwoFactorType::NONE }}">{{ __('None') }}</option>
        <option value="{{ App\Enums\TwoFactorType::AUTHENTICATOR }}">{{ __('Authenticator app') }}</option>
        <option value="{{ App\Enums\TwoFactorType::EMAIL }}">{{ __('Code by email') }}</option>
      </select> --}}
      <x-select id="preferred_method" :options="[
        App\Enums\TwoFactorType::NONE => __('None'),
        App\Enums\TwoFactorType::AUTHENTICATOR => __('Authenticator app'),
        App\Enums\TwoFactorType::EMAIL => __('Code by email')
      ]" selected="{{ App\Enums\TwoFactorType::NONE }}" required :error="$errors->get('preferred_method')" />
    </div>
  </div>

  <!-- actions -->
  <div class="flex items-center justify-end p-3">
    <x-button>{{ __('Save') }}</x-button>
  </div>
</form>

<x-box>
  <!-- Authenticator app -->
  <div id="authenticator-app" class="flex items-center rounded-t-lg border-b border-gray-200 p-3 hover:bg-blue-50">
    <x-phosphor-device-mobile class="h-5 w-5 text-gray-500" />
    <div class="ms-5 flex w-full items-center justify-between">
      <div>
        <p class="font-semibold">
          {{ __('Authenticator app') }}
          @if ($has_2fa)
            <span class="me-2 rounded-sm bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">{{ __('Configured') }}</span>
          @endif
        </p>
        <p class="text-xs text-gray-600">{{ __('Use an authentication app to get two-factor authentication codes when prompted.') }}</p>
      </div>

      @if ($has_2fa)
        <x-button.secondary href="{{ route('administration.security.2fa.new') }}" x-target="authenticator-app" class="mr-2 text-sm">
          {{ __('Edit') }}
        </x-button.secondary>
      @else
        <x-button.secondary href="{{ route('administration.security.2fa.new') }}" x-target="authenticator-app" class="mr-2 text-sm">
          {{ __('Set up') }}
        </x-button.secondary>
      @endif
    </div>
  </x-box>

  <!-- recovery codes -->
  @if ($has_2fa)
    <div id="recovery-codes" class="flex items-center border-b border-gray-200 p-3 hover:bg-blue-50">
      <x-phosphor-toolbox class="h-5 w-5 text-gray-500" />
      <div class="ms-5 flex w-full items-center justify-between">
        <div>
          <p class="font-semibold">
            {{ __('Recovery codes') }}
          </p>
          <p class="text-xs text-gray-600">{{ __('Use these codes to access your account if you lose access to your authenticator app.') }}</p>
        </div>

        <x-button.secondary href="{{ route('administration.security.recoverycodes.show') }}" x-target="recovery-codes" class="mr-2 text-sm">
          {{ __('Show') }}
        </x-button.secondary>
      </div>
    </div>
  @endif

  <!-- Code by email -->
  <div class="flex items-center rounded-b-lg p-3 hover:bg-blue-50">
    <x-phosphor-envelope class="h-5 w-5 text-gray-500" />
    <div class="ms-5 flex w-full items-center justify-between">
      <div class="">
        <p class="font-semibold">{{ __('Code by email') }}</p>
        <p class="text-xs text-gray-600">{{ __('Receive a one-time code via email.') }}</p>
      </div>

      <x-button.secondary class="mr-2 text-sm">
        {{ __('Set up') }}
      </x-button.secondary>
    </div>
  </div>
</div>
