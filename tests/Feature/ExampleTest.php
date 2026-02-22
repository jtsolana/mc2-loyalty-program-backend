<?php

test('home route redirects to admin', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/admin');
});
