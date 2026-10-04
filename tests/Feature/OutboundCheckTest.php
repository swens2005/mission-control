<?php

test('the outbound diagnostic hides behind the deploy token', function () {
    config(['app.deploy_token' => 'correct-token']);

    $this->withToken('wrong-token')
        ->postJson(route('deploy.outbound-check'))
        ->assertNotFound();
});

test('the outbound diagnostic is disabled without a deploy token', function () {
    config(['app.deploy_token' => '']);

    $this->postJson(route('deploy.outbound-check'))->assertNotFound();
});
