<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the autopilot speed is shared with every page, and is 0 in tests', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('tourSpeed', 0));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('tourSpeed', 0));
});

test('the speed comes from config and never goes below 0', function () {
    config(['tour.speed' => 100]);

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('tourSpeed', 100));
});
