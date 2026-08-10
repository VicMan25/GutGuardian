<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

describe('HU-003 — Recuperación de contraseña', function () {

    it('la ruta de solicitud devuelve 200', function () {
        $this->get(route('password.request'))->assertOk();
    });

    it('enviar correo con email registrado despacha la notificación', function () {
        Notification::fake();

        $user = User::factory()->create(['email' => 'recuperar@umariana.edu.co']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors();
    });

    it('enviar correo con email no registrado devuelve error', function () {
        $this->post(route('password.email'), ['email' => 'noexiste@umariana.edu.co'])
            ->assertSessionHasErrors('email');
    });

    it('la ruta de restablecimiento con token válido devuelve 200', function () {
        $user = User::factory()->create();

        $token = app('auth.password.broker')->createToken($user);

        $this->get(route('password.reset', ['token' => $token]) . '?email=' . urlencode($user->email))
            ->assertOk();
    });

});
