<?php

/*
 * The README is the first thing a recruiter reads (story 39): every file,
 * folder and image it points to must exist.
 */

test('every local link and image in the README exists', function () {
    $root = dirname(__DIR__, 2);
    $readme = (string) file_get_contents("{$root}/README.md");

    preg_match_all('/\]\(([^)\s]+)\)/', $readme, $matches);

    $local = array_filter($matches[1], fn (string $target) => ! preg_match('#^(https?:|mailto:|\#)#', $target));

    expect($local)->not->toBeEmpty();

    foreach ($local as $target) {
        expect(file_exists("{$root}/".explode('#', $target)[0]))->toBeTrue("README links to a missing path: {$target}");
    }
});
