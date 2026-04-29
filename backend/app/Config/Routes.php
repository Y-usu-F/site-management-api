<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/health', 'HealthController::health');
$routes->get('/ready', 'HealthController::ready');
$routes->get('/docs', 'DocsController::swagger');
$routes->get('/docs/openapi.yaml', 'DocsController::openapi');

$routes->group('api/v1', ['filter' => ['request-id', 'request-context']], static function ($routes) {
    // Public auth routes
    $routes->group('auth', static function ($routes) {
        $routes->post('login', 'Api\V1\AuthController::login', ['filter' => ['rate-limit:login', 'idempotency']]);
        $routes->post('refresh', 'Api\V1\AuthController::refresh', ['filter' => 'idempotency']);
        $routes->post('forgot-password', 'Api\V1\AuthController::forgotPassword', ['filter' => ['rate-limit:forgot-password', 'idempotency']]);
        $routes->post('reset-password', 'Api\V1\AuthController::resetPassword', ['filter' => ['rate-limit:reset-password', 'idempotency']]);
    });

    // Protected auth routes
    $routes->group('auth', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->post('logout', 'Api\V1\AuthController::logout', [
            'filter' => ['auth-token', 'active-user', 'permission:auth.logout', 'idempotency'],
        ]);
        $routes->get('me', 'Api\V1\AuthController::me', [
            'filter' => ['auth-token', 'active-user', 'permission:auth.me.view'],
        ]);
        $routes->get('sessions', 'Api\Auth\AuthSessionController::index', [
            'filter' => ['auth-token', 'active-user', 'permission:auth.session.list'],
        ]);
        $routes->delete('sessions/(:num)', 'Api\Auth\AuthSessionController::delete/$1', [
            'filter' => ['auth-token', 'active-user', 'permission:auth.session.revoke', 'idempotency'],
        ]);
        $routes->post('sessions/revoke-all', 'Api\Auth\AuthSessionController::revokeAll', [
            'filter' => ['auth-token', 'active-user', 'permission:auth.session.revoke.all', 'idempotency'],
        ]);
    });

    // Profile route baglantilari
    $routes->group('profile', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\ProfileController::show', [
            'filter' => ['auth-token', 'active-user', 'permission:profile.view'],
        ]);
        $routes->put('/', 'Api\V1\ProfileController::update', [
            'filter' => ['auth-token', 'active-user', 'permission:profile.update', 'idempotency'],
        ]);
        $routes->post('change-password', 'Api\V1\ProfileController::changePassword', [
            'filter' => ['auth-token', 'active-user', 'permission:profile.password.change', 'idempotency'],
        ]);
    });

});
