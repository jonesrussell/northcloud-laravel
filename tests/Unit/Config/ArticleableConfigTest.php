<?php

it('has articleable config with defaults', function () {
    $config = config('northcloud.articleable');

    expect($config)->toBeArray();
    expect($config['enabled'])->toBeFalse();
    expect($config['models'])->toBeArray()->toBeEmpty();
});
