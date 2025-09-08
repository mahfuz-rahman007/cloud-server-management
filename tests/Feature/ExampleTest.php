<?php

test('home route redirects unauthenticated users to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/login');
});

test('home route redirects authenticated users to servers', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect('/servers');
});
