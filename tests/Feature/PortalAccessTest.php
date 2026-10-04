<?php

use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Every GET route in a portal, with route parameters filled with a dummy id.
 *
 * @return list<string>
 */
function portalUrls(string $portal): array
{
    return collect(RouteFacade::getRoutes()->getRoutes())
        ->filter(fn (Route $route) => in_array('GET', $route->methods(), true)
            && ($route->uri() === $portal || str_starts_with($route->uri(), "{$portal}/")))
        ->map(fn (Route $route) => '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri()))
        ->values()
        ->all();
}

test('admins log in to Mission Control', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('clients log in to Launchpad', function () {
    $client = User::factory()->client()->create();

    $this->post(route('login.store'), ['email' => $client->email, 'password' => 'password'])
        ->assertRedirect(route('client.home'));
});

test('an intended URL is honoured inside the user\'s own portal', function () {
    $admin = User::factory()->admin()->create();

    $this->withSession(['url.intended' => route('admin.dashboard').'?from=link'])
        ->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard').'?from=link');
});

test('an intended URL in the other portal is ignored', function () {
    $client = User::factory()->client()->create();

    $this->withSession(['url.intended' => route('admin.dashboard')])
        ->post(route('login.store'), ['email' => $client->email, 'password' => 'password'])
        ->assertRedirect(route('client.home'));
});

test('the home page sends each user to their own portal', function () {
    $this->get('/')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect(route('admin.dashboard'));
    $this->actingAs(User::factory()->client()->create())->get('/')->assertRedirect(route('client.home'));
});

test('a signed-in user visiting the login page goes to their portal', function () {
    $this->actingAs(User::factory()->client()->create())
        ->get(route('login'))
        ->assertRedirect('/');
});

test('each portal home renders for its own role', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk();
    $this->actingAs(User::factory()->client()->create())->get(route('client.home'))->assertOk();
});

test('clients get 403 on every admin route', function () {
    $client = User::factory()->client()->create();
    $urls = portalUrls('admin');

    expect($urls)->not->toBeEmpty();

    foreach ($urls as $url) {
        $this->actingAs($client)->get($url)->assertForbidden();
    }
});

test('admins get 403 on every client route', function () {
    $admin = User::factory()->admin()->create();
    $urls = portalUrls('client');

    expect($urls)->not->toBeEmpty();

    foreach ($urls as $url) {
        $this->actingAs($admin)->get($url)->assertForbidden();
    }
});

test('guests are sent to login from every portal route', function () {
    foreach ([...portalUrls('admin'), ...portalUrls('client')] as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }
});

test('both roles can open their account settings', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('profile.edit'))->assertOk();
    $this->actingAs(User::factory()->client()->create())->get(route('profile.edit'))->assertOk();
});
