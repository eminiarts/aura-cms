<?php

namespace Eminiarts\Aura\Http\Livewire\User;

use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Traits\ConfirmsPasswords;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Component;

// This is heavily based on Laravel Jetstream's TwoFactorAuthenticationForm.php
// Thanks to Taylor Otwell and the Laravel Jetstream team!
// https://jetstream.laravel.com/2.x/introduction.html

class TwoFactorAuthenticationForm extends Component
{
    use ConfirmsPasswords;

    /**
     * The OTP code for confirming two factor authentication.
     *
     * @var string|null
     */
    public $code;

    /**
     * Indicates if the two factor authentication confirmation input and button are being displayed.
     *
     * @var bool
     */
    public $showingConfirmation = false;

    /**
     * Indicates if two factor authentication QR code is being displayed.
     *
     * @var bool
     */
    public $showingQrCode = false;

    /**
     * Indicates if two factor authentication recovery codes are being displayed.
     *
     * @var bool
     */
    public $showingRecoveryCodes = false;

    /**
     * Confirm two factor authentication for the user.
     *
     * @return void
     */
    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm)
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }
        $this->ensurePasswordIsConfirmed();


        $confirm($this->user, $this->code);

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = true;
    }

    /**
     * Disable two factor authentication for the user.
     *
     * @return void
     */
    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable)
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }
        $this->ensurePasswordIsConfirmed();


        $disable($this->user);

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = false;
    }

    /**
     * Enable two factor authentication for the user.
     *
     * @return void
     */
    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable)
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }
        $this->ensurePasswordIsConfirmed();

        $enable($this->user);

        $this->showingQrCode = true;
        $this->showingConfirmation = true;

        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
        //     $this->showingConfirmation = true;
        // } else {
        //     $this->showingRecoveryCodes = true;
        // }
    }

    /**
     * Determine if two factor authentication is enabled.
     *
     * @return bool
     */
    public function getEnabledProperty()
    {
        return ! empty($this->user->two_factor_secret);
    }

    /**
     * Mount the component.
     *
     * @return void
     */
    public function mount()
    {
        if (is_null($this->user->two_factor_confirmed_at)) {
            app(DisableTwoFactorAuthentication::class)($this->user);
        }
    }

    /**
     * Get the current user of the application.
     *
     * @return mixed
     */
    public function getUserProperty()
    {
        return auth()->user();
    }

    /**
     * Generate new recovery codes for the user.
     *
     * @return void
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate)
    {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            $this->ensurePasswordIsConfirmed();
        }

        $generate($this->user);

        $this->showingRecoveryCodes = true;
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('aura::profile.two-factor-authentication-form')->layout('aura::components.layout.app');
    }

    /**
     * Display the user's recovery codes.
     *
     * @return void
     */
    public function showRecoveryCodes()
    {
        // if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
        //     $this->ensurePasswordIsConfirmed();
        // }
        $this->ensurePasswordIsConfirmed();

        $this->showingRecoveryCodes = true;
    }
}
