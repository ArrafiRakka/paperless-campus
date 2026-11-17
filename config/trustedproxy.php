<?php

$trustedProxies = env('TRUSTED_PROXIES');
$isRailwayProduction = env('APP_ENV', 'production') === 'production'
    && filled(env('RAILWAY_ENVIRONMENT_NAME'));

if ($trustedProxies === '*') {
    // Railway terminates HTTPS at its trusted proxy. Never enable trust-all
    // for local, test, or non-Railway environments.
    $proxies = $isRailwayProduction ? '*' : null;
} elseif (is_string($trustedProxies) && trim($trustedProxies) !== '') {
    $proxies = array_values(array_filter(
        array_map('trim', explode(',', $trustedProxies)),
        fn (string $proxy): bool => $proxy !== '' && $proxy !== '*',
    ));
} else {
    $proxies = null;
}

return ['proxies' => $proxies];
