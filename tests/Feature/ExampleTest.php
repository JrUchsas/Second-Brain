<?php

it('returns welcome page response for root route', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
