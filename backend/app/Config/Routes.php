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

    // Site management route baglantilari
    $routes->group('sites', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\SiteController::index', ['filter' => ['auth-token', 'active-user', 'permission:site.list']]);
        $routes->post('/', 'Api\V1\SiteController::create', ['filter' => ['auth-token', 'active-user', 'permission:site.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\SiteController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:site.view']]);
        $routes->put('(:num)', 'Api\V1\SiteController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:site.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\SiteController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:site.delete', 'idempotency']]);
    });

    $routes->group('blocks', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\BlockController::index', ['filter' => ['auth-token', 'active-user', 'permission:block.list']]);
        $routes->post('/', 'Api\V1\BlockController::create', ['filter' => ['auth-token', 'active-user', 'permission:block.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\BlockController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:block.view']]);
        $routes->put('(:num)', 'Api\V1\BlockController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:block.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\BlockController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:block.delete', 'idempotency']]);
    });

    $routes->group('floors', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\FloorController::index', ['filter' => ['auth-token', 'active-user', 'permission:floor.list']]);
        $routes->post('/', 'Api\V1\FloorController::create', ['filter' => ['auth-token', 'active-user', 'permission:floor.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\FloorController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:floor.view']]);
        $routes->put('(:num)', 'Api\V1\FloorController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:floor.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\FloorController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:floor.delete', 'idempotency']]);
    });

    $routes->group('units', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\UnitController::index', ['filter' => ['auth-token', 'active-user', 'permission:unit.list']]);
        $routes->post('/', 'Api\V1\UnitController::create', ['filter' => ['auth-token', 'active-user', 'permission:unit.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\UnitController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:unit.view']]);
        $routes->put('(:num)', 'Api\V1\UnitController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:unit.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\UnitController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:unit.delete', 'idempotency']]);
    });

    $routes->group('residents', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\ResidentProfileController::index', ['filter' => ['auth-token', 'active-user', 'permission:resident.list']]);
        $routes->post('/', 'Api\V1\ResidentProfileController::create', ['filter' => ['auth-token', 'active-user', 'permission:resident.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\ResidentProfileController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident.view']]);
        $routes->put('(:num)', 'Api\V1\ResidentProfileController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\ResidentProfileController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident.delete', 'idempotency']]);
    });

    $routes->group('unit-occupancies', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\UnitOccupancyController::index', ['filter' => ['auth-token', 'active-user', 'permission:unit_occupancy.list']]);
        $routes->post('/', 'Api\V1\UnitOccupancyController::create', ['filter' => ['auth-token', 'active-user', 'permission:unit_occupancy.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\UnitOccupancyController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:unit_occupancy.view']]);
        $routes->put('(:num)', 'Api\V1\UnitOccupancyController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:unit_occupancy.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\UnitOccupancyController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:unit_occupancy.delete', 'idempotency']]);
    });

    $routes->group('resident-contacts', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\ResidentContactController::index', ['filter' => ['auth-token', 'active-user', 'permission:resident_contact.list']]);
        $routes->post('/', 'Api\V1\ResidentContactController::create', ['filter' => ['auth-token', 'active-user', 'permission:resident_contact.create', 'idempotency']]);
        $routes->put('(:num)', 'Api\V1\ResidentContactController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident_contact.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\ResidentContactController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident_contact.delete', 'idempotency']]);
    });

    $routes->group('resident-vehicles', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\ResidentVehicleController::index', ['filter' => ['auth-token', 'active-user', 'permission:resident_vehicle.list']]);
        $routes->post('/', 'Api\V1\ResidentVehicleController::create', ['filter' => ['auth-token', 'active-user', 'permission:resident_vehicle.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\ResidentVehicleController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident_vehicle.view']]);
        $routes->put('(:num)', 'Api\V1\ResidentVehicleController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident_vehicle.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\ResidentVehicleController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:resident_vehicle.delete', 'idempotency']]);
    });

    $routes->group('due-definitions', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DueDefinitionController::index', ['filter' => ['auth-token', 'active-user', 'permission:due_definition.list']]);
        $routes->post('/', 'Api\V1\DueDefinitionController::create', ['filter' => ['auth-token', 'active-user', 'permission:due_definition.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\DueDefinitionController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_definition.view']]);
        $routes->put('(:num)', 'Api\V1\DueDefinitionController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_definition.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\DueDefinitionController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_definition.delete', 'idempotency']]);
    });

    $routes->group('due-periods', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DuePeriodController::index', ['filter' => ['auth-token', 'active-user', 'permission:due_period.list']]);
        $routes->post('/', 'Api\V1\DuePeriodController::create', ['filter' => ['auth-token', 'active-user', 'permission:due_period.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\DuePeriodController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_period.view']]);
        $routes->put('(:num)', 'Api\V1\DuePeriodController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_period.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\DuePeriodController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_period.delete', 'idempotency']]);
        $routes->post('(:num)/close', 'Api\V1\DuePeriodController::close/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_period.close', 'idempotency']]);
        $routes->post('(:num)/lock', 'Api\V1\DuePeriodController::lock/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_period.lock', 'idempotency']]);
    });

    $routes->group('due-batches', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DueBatchController::index', ['filter' => ['auth-token', 'active-user', 'permission:due_batch.list']]);
        $routes->get('(:num)', 'Api\V1\DueBatchController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_batch.view']]);
        $routes->post('/', 'Api\V1\DueBatchController::create', ['filter' => ['auth-token', 'active-user', 'permission:due_batch.create', 'idempotency']]);
    });

    $routes->group('due-items', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DueItemController::index', ['filter' => ['auth-token', 'active-user', 'permission:due_item.list']]);
        $routes->get('(:num)', 'Api\V1\DueItemController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_item.view']]);
        $routes->put('(:num)', 'Api\V1\DueItemController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_item.update', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\DueItemController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:due_item.cancel', 'idempotency']]);
    });

    $routes->group('payments', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\PaymentController::index', ['filter' => ['auth-token', 'active-user', 'permission:payment.list']]);
        $routes->post('manual', 'Api\V1\PaymentController::createManual', ['filter' => ['auth-token', 'active-user', 'permission:payment.create_manual', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\PaymentController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:payment.view']]);
        $routes->post('(:num)/cancel', 'Api\V1\PaymentController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:payment.cancel', 'idempotency']]);
    });

    $routes->group('payment-events', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\PaymentEventController::index', ['filter' => ['auth-token', 'active-user', 'permission:payment_event.list']]);
        $routes->get('(:num)', 'Api\V1\PaymentEventController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:payment_event.view']]);
    });
    $routes->group('deposits', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DepositController::index', ['filter' => ['auth-token', 'active-user', 'permission:deposit.list']]);
        $routes->post('/', 'Api\V1\DepositController::create', ['filter' => ['auth-token', 'active-user', 'permission:deposit.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\DepositController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit.view']]);
        $routes->put('(:num)', 'Api\V1\DepositController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit.update', 'idempotency']]);
        $routes->post('(:num)/receive', 'Api\V1\DepositController::receive/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit.receive', 'idempotency']]);
        $routes->post('(:num)/refund', 'Api\V1\DepositController::refund/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit.refund', 'idempotency']]);
        $routes->post('(:num)/deduct', 'Api\V1\DepositController::deduct/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit.deduct', 'idempotency']]);
        $routes->post('(:num)/apply-to-debt', 'Api\V1\DepositController::applyToDebt/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit.apply_to_debt', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\DepositController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit.cancel', 'idempotency']]);
        $routes->get('(:num)/transactions', 'Api\V1\DepositTransactionController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit_transaction.list']]);
    });
    $routes->group('deposit-transactions', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('(:num)', 'Api\V1\DepositTransactionController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:deposit_transaction.view']]);
    });

    $routes->group('request-categories', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\RequestCategoryController::index', ['filter' => ['auth-token', 'active-user', 'permission:request_category.list']]);
        $routes->post('/', 'Api\V1\RequestCategoryController::create', ['filter' => ['auth-token', 'active-user', 'permission:request_category.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\RequestCategoryController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:request_category.view']]);
        $routes->put('(:num)', 'Api\V1\RequestCategoryController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:request_category.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\RequestCategoryController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:request_category.delete', 'idempotency']]);
    });

    $routes->group('service-requests', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\ServiceRequestController::index', ['filter' => ['auth-token', 'active-user', 'permission:service_request.list']]);
        $routes->post('/', 'Api\V1\ServiceRequestController::create', ['filter' => ['auth-token', 'active-user', 'permission:service_request.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\ServiceRequestController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request.view']]);
        $routes->put('(:num)', 'Api\V1\ServiceRequestController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request.update', 'idempotency']]);
        $routes->post('(:num)/assign', 'Api\V1\ServiceRequestController::assign/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request.assign', 'idempotency']]);
        $routes->post('(:num)/resolve', 'Api\V1\ServiceRequestController::resolve/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request.resolve', 'idempotency']]);
        $routes->post('(:num)/close', 'Api\V1\ServiceRequestController::close/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request.close', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\ServiceRequestController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request.cancel', 'idempotency']]);
        $routes->get('(:num)/comments', 'Api\V1\ServiceRequestCommentController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request_comment.list']]);
        $routes->post('(:num)/comments', 'Api\V1\ServiceRequestCommentController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request_comment.create', 'idempotency']]);
        $routes->get('(:num)/files', 'Api\V1\ServiceRequestFileController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request_file.list']]);
        $routes->post('(:num)/files', 'Api\V1\ServiceRequestFileController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request_file.create', 'idempotency']]);
    });
    $routes->delete('service-request-files/(:num)', 'Api\V1\ServiceRequestFileController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:service_request_file.delete', 'idempotency']]);

    $routes->group('work-orders', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\WorkOrderController::index', ['filter' => ['auth-token', 'active-user', 'permission:work_order.list']]);
        $routes->post('/', 'Api\V1\WorkOrderController::create', ['filter' => ['auth-token', 'active-user', 'permission:work_order.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\WorkOrderController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:work_order.view']]);
        $routes->put('(:num)', 'Api\V1\WorkOrderController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:work_order.update', 'idempotency']]);
        $routes->post('(:num)/start', 'Api\V1\WorkOrderController::start/$1', ['filter' => ['auth-token', 'active-user', 'permission:work_order.start', 'idempotency']]);
        $routes->post('(:num)/complete', 'Api\V1\WorkOrderController::complete/$1', ['filter' => ['auth-token', 'active-user', 'permission:work_order.complete', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\WorkOrderController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:work_order.cancel', 'idempotency']]);
    });

    $routes->group('notification-templates', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\NotificationTemplateController::index', ['filter' => ['auth-token', 'active-user', 'permission:notification_template.list']]);
        $routes->post('/', 'Api\V1\NotificationTemplateController::create', ['filter' => ['auth-token', 'active-user', 'permission:notification_template.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\NotificationTemplateController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_template.view']]);
        $routes->put('(:num)', 'Api\V1\NotificationTemplateController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_template.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\NotificationTemplateController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_template.delete', 'idempotency']]);
    });
    $routes->group('notification-messages', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\NotificationMessageController::index', ['filter' => ['auth-token', 'active-user', 'permission:notification_message.list']]);
        $routes->post('/', 'Api\V1\NotificationMessageController::create', ['filter' => ['auth-token', 'active-user', 'permission:notification_message.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\NotificationMessageController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_message.view']]);
        $routes->post('(:num)/queue', 'Api\V1\NotificationMessageController::queue/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_message.queue', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\NotificationMessageController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_message.cancel', 'idempotency']]);
    });
    $routes->group('notification-recipients', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\NotificationRecipientController::index', ['filter' => ['auth-token', 'active-user', 'permission:notification_recipient.list']]);
        $routes->get('(:num)', 'Api\V1\NotificationRecipientController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_recipient.view']]);
        $routes->post('(:num)/mark-read', 'Api\V1\NotificationRecipientController::markRead/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_recipient.mark_read', 'idempotency']]);
    });
    $routes->group('notification-delivery-logs', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\NotificationDeliveryLogController::index', ['filter' => ['auth-token', 'active-user', 'permission:notification_delivery_log.list']]);
        $routes->get('(:num)', 'Api\V1\NotificationDeliveryLogController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:notification_delivery_log.view']]);
    });
    $routes->group('communication-providers', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\CommunicationProviderController::index', ['filter' => ['auth-token', 'active-user', 'permission:communication_provider.list']]);
        $routes->post('/', 'Api\V1\CommunicationProviderController::create', ['filter' => ['auth-token', 'active-user', 'permission:communication_provider.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\CommunicationProviderController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:communication_provider.view']]);
        $routes->put('(:num)', 'Api\V1\CommunicationProviderController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:communication_provider.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\CommunicationProviderController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:communication_provider.delete', 'idempotency']]);
        $routes->post('(:num)/set-default', 'Api\V1\CommunicationProviderController::setDefault/$1', ['filter' => ['auth-token', 'active-user', 'permission:communication_provider.set_default', 'idempotency']]);
    });

    $routes->group('announcements', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\AnnouncementController::index', ['filter' => ['auth-token', 'active-user', 'permission:announcement.list']]);
        $routes->post('/', 'Api\V1\AnnouncementController::create', ['filter' => ['auth-token', 'active-user', 'permission:announcement.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\AnnouncementController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.view']]);
        $routes->put('(:num)', 'Api\V1\AnnouncementController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\AnnouncementController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.delete', 'idempotency']]);
        $routes->post('(:num)/publish', 'Api\V1\AnnouncementController::publish/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.publish', 'idempotency']]);
        $routes->post('(:num)/archive', 'Api\V1\AnnouncementController::archive/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.archive', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\AnnouncementController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.cancel', 'idempotency']]);
        $routes->post('(:num)/mark-read', 'Api\V1\AnnouncementController::markRead/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.mark_read', 'idempotency']]);
        $routes->get('(:num)/reads', 'Api\V1\AnnouncementController::reads/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.reads.list']]);
        $routes->get('(:num)/targets', 'Api\V1\AnnouncementController::targets/$1', ['filter' => ['auth-token', 'active-user', 'permission:announcement.targets.list']]);
    });

    $routes->group('common-areas', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\CommonAreaController::index', ['filter' => ['auth-token', 'active-user', 'permission:common_area.list']]);
        $routes->post('/', 'Api\V1\CommonAreaController::create', ['filter' => ['auth-token', 'active-user', 'permission:common_area.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\CommonAreaController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area.view']]);
        $routes->put('(:num)', 'Api\V1\CommonAreaController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\CommonAreaController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area.delete', 'idempotency']]);
    });
    $routes->group('common-area-reservations', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\CommonAreaReservationController::index', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.list']]);
        $routes->post('/', 'Api\V1\CommonAreaReservationController::create', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\CommonAreaReservationController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.view']]);
        $routes->put('(:num)', 'Api\V1\CommonAreaReservationController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.update', 'idempotency']]);
        $routes->post('(:num)/approve', 'Api\V1\CommonAreaReservationController::approve/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.approve', 'idempotency']]);
        $routes->post('(:num)/reject', 'Api\V1\CommonAreaReservationController::reject/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.reject', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\CommonAreaReservationController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.cancel', 'idempotency']]);
        $routes->post('(:num)/complete', 'Api\V1\CommonAreaReservationController::complete/$1', ['filter' => ['auth-token', 'active-user', 'permission:common_area_reservation.complete', 'idempotency']]);
    });

    $routes->group('meters', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\MeterController::index', ['filter' => ['auth-token', 'active-user', 'permission:meter.list']]);
        $routes->post('/', 'Api\V1\MeterController::create', ['filter' => ['auth-token', 'active-user', 'permission:meter.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\MeterController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter.view']]);
        $routes->put('(:num)', 'Api\V1\MeterController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\MeterController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter.delete', 'idempotency']]);
    });
    $routes->group('meter-reading-periods', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\MeterReadingPeriodController::index', ['filter' => ['auth-token', 'active-user', 'permission:meter_period.list']]);
        $routes->post('/', 'Api\V1\MeterReadingPeriodController::create', ['filter' => ['auth-token', 'active-user', 'permission:meter_period.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\MeterReadingPeriodController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_period.view']]);
        $routes->put('(:num)', 'Api\V1\MeterReadingPeriodController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_period.update', 'idempotency']]);
        $routes->post('(:num)/close', 'Api\V1\MeterReadingPeriodController::close/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_period.close', 'idempotency']]);
        $routes->post('(:num)/lock', 'Api\V1\MeterReadingPeriodController::lock/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_period.lock', 'idempotency']]);
    });
    $routes->group('meter-readings', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\MeterReadingController::index', ['filter' => ['auth-token', 'active-user', 'permission:meter_reading.list']]);
        $routes->post('/', 'Api\V1\MeterReadingController::create', ['filter' => ['auth-token', 'active-user', 'permission:meter_reading.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\MeterReadingController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_reading.view']]);
        $routes->put('(:num)', 'Api\V1\MeterReadingController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_reading.update', 'idempotency']]);
        $routes->post('(:num)/approve', 'Api\V1\MeterReadingController::approve/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_reading.approve', 'idempotency']]);
        $routes->post('(:num)/reject', 'Api\V1\MeterReadingController::reject/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_reading.reject', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\MeterReadingController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:meter_reading.cancel', 'idempotency']]);
        $routes->post('(:num)/generate-consumption-report', 'Api\V1\MeterReadingController::generateConsumptionReport/$1', ['filter' => ['auth-token', 'active-user', 'permission:consumption_report.generate', 'idempotency']]);
    });
    $routes->group('consumption-reports', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\ConsumptionReportController::index', ['filter' => ['auth-token', 'active-user', 'permission:consumption_report.list']]);
        $routes->get('(:num)', 'Api\V1\ConsumptionReportController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:consumption_report.view']]);
        $routes->post('(:num)/cancel', 'Api\V1\ConsumptionReportController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:consumption_report.cancel', 'idempotency']]);
    });
    $routes->group('assets', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\AssetController::index', ['filter' => ['auth-token', 'active-user', 'permission:asset.list']]);
        $routes->post('/', 'Api\V1\AssetController::create', ['filter' => ['auth-token', 'active-user', 'permission:asset.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\AssetController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset.view']]);
        $routes->put('(:num)', 'Api\V1\AssetController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\AssetController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset.delete', 'idempotency']]);
    });
    $routes->group('asset-maintenance-plans', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\AssetMaintenancePlanController::index', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_plan.list']]);
        $routes->post('/', 'Api\V1\AssetMaintenancePlanController::create', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_plan.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\AssetMaintenancePlanController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_plan.view']]);
        $routes->put('(:num)', 'Api\V1\AssetMaintenancePlanController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_plan.update', 'idempotency']]);
        $routes->post('(:num)/pause', 'Api\V1\AssetMaintenancePlanController::pause/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_plan.pause', 'idempotency']]);
        $routes->post('(:num)/resume', 'Api\V1\AssetMaintenancePlanController::resume/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_plan.resume', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\AssetMaintenancePlanController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_plan.cancel', 'idempotency']]);
    });
    $routes->group('asset-maintenance-records', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\AssetMaintenanceRecordController::index', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_record.list']]);
        $routes->post('/', 'Api\V1\AssetMaintenanceRecordController::create', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_record.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\AssetMaintenanceRecordController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_record.view']]);
        $routes->post('(:num)/cancel', 'Api\V1\AssetMaintenanceRecordController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:asset_maintenance_record.cancel', 'idempotency']]);
    });
    $routes->group('visitor-invites', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\VisitorInviteController::index', ['filter' => ['auth-token', 'active-user', 'permission:visitor_invite.list']]);
        $routes->post('/', 'Api\V1\VisitorInviteController::create', ['filter' => ['auth-token', 'active-user', 'permission:visitor_invite.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\VisitorInviteController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:visitor_invite.view']]);
        $routes->post('(:num)/cancel', 'Api\V1\VisitorInviteController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:visitor_invite.cancel', 'idempotency']]);
    });
    $routes->group('visitor-entries', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\VisitorEntryController::index', ['filter' => ['auth-token', 'active-user', 'permission:visitor_entry.list']]);
        $routes->post('check-in', 'Api\V1\VisitorEntryController::checkIn', ['filter' => ['auth-token', 'active-user', 'permission:visitor_entry.check_in', 'idempotency']]);
        $routes->post('check-out', 'Api\V1\VisitorEntryController::checkOut', ['filter' => ['auth-token', 'active-user', 'permission:visitor_entry.check_out', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\VisitorEntryController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:visitor_entry.view']]);
    });
    $routes->group('security-incidents', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\SecurityIncidentController::index', ['filter' => ['auth-token', 'active-user', 'permission:security_incident.list']]);
        $routes->post('/', 'Api\V1\SecurityIncidentController::create', ['filter' => ['auth-token', 'active-user', 'permission:security_incident.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\SecurityIncidentController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:security_incident.view']]);
        $routes->put('(:num)', 'Api\V1\SecurityIncidentController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:security_incident.update', 'idempotency']]);
        $routes->post('(:num)/resolve', 'Api\V1\SecurityIncidentController::resolve/$1', ['filter' => ['auth-token', 'active-user', 'permission:security_incident.resolve', 'idempotency']]);
        $routes->post('(:num)/close', 'Api\V1\SecurityIncidentController::close/$1', ['filter' => ['auth-token', 'active-user', 'permission:security_incident.close', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\SecurityIncidentController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:security_incident.cancel', 'idempotency']]);
    });
    $routes->group('vehicle-access-lists', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\VehicleAccessListController::index', ['filter' => ['auth-token', 'active-user', 'permission:vehicle_access_list.list']]);
        $routes->post('/', 'Api\V1\VehicleAccessListController::create', ['filter' => ['auth-token', 'active-user', 'permission:vehicle_access_list.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\VehicleAccessListController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:vehicle_access_list.view']]);
        $routes->put('(:num)', 'Api\V1\VehicleAccessListController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:vehicle_access_list.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\VehicleAccessListController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:vehicle_access_list.delete', 'idempotency']]);
    });
    $routes->group('staff-profiles', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\StaffProfileController::index', ['filter' => ['auth-token', 'active-user', 'permission:staff_profile.list']]);
        $routes->post('/', 'Api\V1\StaffProfileController::create', ['filter' => ['auth-token', 'active-user', 'permission:staff_profile.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\StaffProfileController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_profile.view']]);
        $routes->put('(:num)', 'Api\V1\StaffProfileController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_profile.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\StaffProfileController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_profile.delete', 'idempotency']]);
    });
    $routes->group('staff-assignments', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\StaffAssignmentController::index', ['filter' => ['auth-token', 'active-user', 'permission:staff_assignment.list']]);
        $routes->post('/', 'Api\V1\StaffAssignmentController::create', ['filter' => ['auth-token', 'active-user', 'permission:staff_assignment.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\StaffAssignmentController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_assignment.view']]);
        $routes->put('(:num)', 'Api\V1\StaffAssignmentController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_assignment.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\StaffAssignmentController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_assignment.delete', 'idempotency']]);
    });
    $routes->group('staff-shifts', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\StaffShiftController::index', ['filter' => ['auth-token', 'active-user', 'permission:staff_shift.list']]);
        $routes->post('/', 'Api\V1\StaffShiftController::create', ['filter' => ['auth-token', 'active-user', 'permission:staff_shift.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\StaffShiftController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_shift.view']]);
        $routes->put('(:num)', 'Api\V1\StaffShiftController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_shift.update', 'idempotency']]);
        $routes->post('(:num)/start', 'Api\V1\StaffShiftController::start/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_shift.start', 'idempotency']]);
        $routes->post('(:num)/complete', 'Api\V1\StaffShiftController::complete/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_shift.complete', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\StaffShiftController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_shift.cancel', 'idempotency']]);
    });
    $routes->group('staff-tasks', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\StaffTaskController::index', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.list']]);
        $routes->post('/', 'Api\V1\StaffTaskController::create', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\StaffTaskController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.view']]);
        $routes->put('(:num)', 'Api\V1\StaffTaskController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.update', 'idempotency']]);
        $routes->post('(:num)/assign', 'Api\V1\StaffTaskController::assign/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.assign', 'idempotency']]);
        $routes->post('(:num)/start', 'Api\V1\StaffTaskController::start/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.start', 'idempotency']]);
        $routes->post('(:num)/complete', 'Api\V1\StaffTaskController::complete/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.complete', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\StaffTaskController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:staff_task.cancel', 'idempotency']]);
    });
    $routes->group('document-categories', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DocumentCategoryController::index', ['filter' => ['auth-token', 'active-user', 'permission:document_category.list']]);
        $routes->post('/', 'Api\V1\DocumentCategoryController::create', ['filter' => ['auth-token', 'active-user', 'permission:document_category.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\DocumentCategoryController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_category.view']]);
        $routes->put('(:num)', 'Api\V1\DocumentCategoryController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_category.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\DocumentCategoryController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_category.delete', 'idempotency']]);
    });
    $routes->group('documents', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DocumentController::index', ['filter' => ['auth-token', 'active-user', 'permission:document.list']]);
        $routes->post('/', 'Api\V1\DocumentController::create', ['filter' => ['auth-token', 'active-user', 'permission:document.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\DocumentController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:document.view']]);
        $routes->put('(:num)', 'Api\V1\DocumentController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:document.update', 'idempotency']]);
        $routes->post('(:num)/archive', 'Api\V1\DocumentController::archive/$1', ['filter' => ['auth-token', 'active-user', 'permission:document.archive', 'idempotency']]);
        $routes->post('(:num)/restore', 'Api\V1\DocumentController::restore/$1', ['filter' => ['auth-token', 'active-user', 'permission:document.restore', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\DocumentController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:document.delete', 'idempotency']]);
        $routes->get('(:num)/versions', 'Api\V1\DocumentVersionController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_version.list']]);
        $routes->post('(:num)/versions', 'Api\V1\DocumentVersionController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_version.create', 'idempotency']]);
        $routes->get('(:num)/access-rules', 'Api\V1\DocumentAccessRuleController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_access_rule.list']]);
        $routes->post('(:num)/access-rules', 'Api\V1\DocumentAccessRuleController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_access_rule.create', 'idempotency']]);
    });
    $routes->group('document-versions', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('(:num)', 'Api\V1\DocumentVersionController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_version.view']]);
    });
    $routes->group('document-access-rules', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->delete('(:num)', 'Api\V1\DocumentAccessRuleController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:document_access_rule.delete', 'idempotency']]);
    });
    $routes->group('meetings', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\MeetingController::index', ['filter' => ['auth-token', 'active-user', 'permission:meeting.list']]);
        $routes->post('/', 'Api\V1\MeetingController::create', ['filter' => ['auth-token', 'active-user', 'permission:meeting.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\MeetingController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting.view']]);
        $routes->put('(:num)', 'Api\V1\MeetingController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting.update', 'idempotency']]);
        $routes->post('(:num)/publish', 'Api\V1\MeetingController::publish/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting.publish', 'idempotency']]);
        $routes->post('(:num)/complete', 'Api\V1\MeetingController::complete/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting.complete', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\MeetingController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting.cancel', 'idempotency']]);
        $routes->post('(:num)/lock', 'Api\V1\MeetingController::lock/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting.lock', 'idempotency']]);
        $routes->get('(:num)/agenda', 'Api\V1\MeetingAgendaController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_agenda.list']]);
        $routes->post('(:num)/agenda', 'Api\V1\MeetingAgendaController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_agenda.create', 'idempotency']]);
        $routes->get('(:num)/attendees', 'Api\V1\MeetingAttendeeController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_attendee.list']]);
        $routes->post('(:num)/attendees', 'Api\V1\MeetingAttendeeController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_attendee.create', 'idempotency']]);
    });
    $routes->group('meeting-agenda-items', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->put('(:num)', 'Api\V1\MeetingAgendaController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_agenda.update', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\MeetingAgendaController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_agenda.delete', 'idempotency']]);
    });
    $routes->group('meeting-attendees', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->put('(:num)', 'Api\V1\MeetingAttendeeController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_attendee.update', 'idempotency']]);
        $routes->post('(:num)/sign', 'Api\V1\MeetingAttendeeController::sign/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_attendee.sign', 'idempotency']]);
        $routes->delete('(:num)', 'Api\V1\MeetingAttendeeController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:meeting_attendee.delete', 'idempotency']]);
    });
    $routes->group('decision-book-entries', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\DecisionBookController::index', ['filter' => ['auth-token', 'active-user', 'permission:decision_book.list']]);
        $routes->post('/', 'Api\V1\DecisionBookController::create', ['filter' => ['auth-token', 'active-user', 'permission:decision_book.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\DecisionBookController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:decision_book.view']]);
        $routes->put('(:num)', 'Api\V1\DecisionBookController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:decision_book.update', 'idempotency']]);
        $routes->post('(:num)/approve', 'Api\V1\DecisionBookController::approve/$1', ['filter' => ['auth-token', 'active-user', 'permission:decision_book.approve', 'idempotency']]);
        $routes->post('(:num)/lock', 'Api\V1\DecisionBookController::lock/$1', ['filter' => ['auth-token', 'active-user', 'permission:decision_book.lock', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\DecisionBookController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:decision_book.cancel', 'idempotency']]);
    });
    $routes->group('legal-cases', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->get('/', 'Api\V1\LegalCaseController::index', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.list']]);
        $routes->post('/', 'Api\V1\LegalCaseController::create', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.create', 'idempotency']]);
        $routes->get('(:num)', 'Api\V1\LegalCaseController::show/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.view']]);
        $routes->put('(:num)', 'Api\V1\LegalCaseController::update/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.update', 'idempotency']]);
        $routes->post('(:num)/send-to-lawyer', 'Api\V1\LegalCaseController::sendToLawyer/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.send_to_lawyer', 'idempotency']]);
        $routes->post('(:num)/file', 'Api\V1\LegalCaseController::file/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.file', 'idempotency']]);
        $routes->post('(:num)/mark-paid', 'Api\V1\LegalCaseController::markPaid/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.mark_paid', 'idempotency']]);
        $routes->post('(:num)/close', 'Api\V1\LegalCaseController::close/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.close', 'idempotency']]);
        $routes->post('(:num)/cancel', 'Api\V1\LegalCaseController::cancel/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case.cancel', 'idempotency']]);
        $routes->get('(:num)/debts', 'Api\V1\LegalCaseDebtController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_debt.list']]);
        $routes->post('(:num)/debts', 'Api\V1\LegalCaseDebtController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_debt.create', 'idempotency']]);
        $routes->get('(:num)/events', 'Api\V1\LegalCaseEventController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_event.list']]);
        $routes->post('(:num)/events', 'Api\V1\LegalCaseEventController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_event.create', 'idempotency']]);
        $routes->get('(:num)/documents', 'Api\V1\LegalCaseDocumentController::index/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_document.list']]);
        $routes->post('(:num)/documents', 'Api\V1\LegalCaseDocumentController::create/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_document.create', 'idempotency']]);
    });
    $routes->group('legal-case-debts', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->delete('(:num)', 'Api\V1\LegalCaseDebtController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_debt.delete', 'idempotency']]);
    });
    $routes->group('legal-case-documents', ['filter' => ['auth-token', 'active-user']], static function ($routes) {
        $routes->delete('(:num)', 'Api\V1\LegalCaseDocumentController::delete/$1', ['filter' => ['auth-token', 'active-user', 'permission:legal_case_document.delete', 'idempotency']]);
    });

});
