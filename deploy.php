<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/symfony.php';

set('repository', 'git@github.com:johnnoel/chaostangent.com.git');
set('keep_releases', 3);
set('artifact_url', '');
set('circle_ci_token', '');
add('shared_dirs', [ 'public/media' ]);

host('chaostangent.com')
    ->set('hostname', '134.122.111.6')
    ->set('remote_user', 'chaostangent-com')
    ->set('deploy_path', '~/www')
    ->setLabels([ 'stage' => 'production' ])
;

host('beta.chaostangent.com')
    ->set('hostname', '91.98.161.234')
    ->set('remote_user', 'beta-chaostangent-com')
    ->set('deploy_path', '~/www')
    ->setLabels([ 'stage' => 'beta' ])
;

task('deploy:update_code', function (): void {
    $artifactUrl = get('artifact_url');
    $circleCiToken = get('circle_ci_token');

    $url = escapeshellarg($artifactUrl);

    $jsonRaw = run('curl -sH ' . escapeshellarg('Circle-Token: ' . $circleCiToken) . ' ' . $url);
    $json = json_decode($jsonRaw, associative: true, flags: JSON_THROW_ON_ERROR);

    $url = escapeshellarg($json[0]['url'] . '?' . http_build_query([ 'circle-token' => $circleCiToken ]));

    run('wget -qO chaostangent-com.tar.bz2 ' . $url);
    run('tar xjf chaostangent-com.tar.bz2 -C {{release_path}}');
});

task('deploy:vendors', function (): void {
    // don't need vendors when the code package is pre-installed
});

after('deploy:failed', 'deploy:unlock');
