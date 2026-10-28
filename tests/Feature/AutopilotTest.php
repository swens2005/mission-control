<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests get the autopilot page, open to search engines', function () {
    $this->get(route('autopilot.show'))
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertSee('<meta name="description" content="Mission Control runs a small web studio', false)
        ->assertInertia(fn (Assert $page) => $page->component('public/autopilot')
            ->where('demoEnabled', true)
            ->where('signedInAs', null));
});

test('a signed-in visitor is told a tour starts a new demo', function () {
    $this->actingAs(User::factory()->admin()->create(['name' => 'Sam Visser']))
        ->get(route('autopilot.show'))
        ->assertInertia(fn (Assert $page) => $page->where('signedInAs', 'Sam Visser'));
});

test('the page says so when the demo is off', function () {
    config(['demo.enabled' => false]);

    $this->get(route('autopilot.show'))
        ->assertInertia(fn (Assert $page) => $page->where('demoEnabled', false));
});

test('a fresh start logs out and opens the login page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->post(route('autopilot.fresh'))
        ->assertRedirect(route('login'));

    $this->assertGuest();

    // Already logged out: still the login page.
    $this->post(route('autopilot.fresh'))->assertRedirect(route('login'));
});
