<?php

declare(strict_types=1);

it('has linking config with defaults', function () {
    expect(config('northcloud.linking.enabled'))->toBeFalse();
    expect(config('northcloud.linking.threshold'))->toBe(0.3);
    expect(config('northcloud.linking.weights.title_match'))->toBe(0.5);
    expect(config('northcloud.linking.weights.tag_overlap'))->toBe(0.3);
    expect(config('northcloud.linking.weights.metadata_match'))->toBe(0.2);
    expect(config('northcloud.linking.min_keyword_length'))->toBe(3);
});
