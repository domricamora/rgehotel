<?php
/**
 * Route table. Returns a closure that registers routes on the Router.
 */

use App\Core\Router;

return function (Router $r) {

    /* -------------------- Public site -------------------- */
    $r->get('/',                         'Public\HomeController@index');
    $r->get('/accommodations',           'Public\RoomController@index');
    $r->get('/accommodations/{slug}',    'Public\RoomController@show');

    $r->get('/services',                 'Public\ServiceController@index');
    $r->get('/services/{slug}',          'Public\ServiceController@show');

    $r->get('/packages',                 'Public\PackageController@index');
    $r->get('/packages/{slug}',          'Public\PackageController@show');

    $r->get('/offers',                   'Public\OfferController@index');

    $r->get('/reviews',                  'Public\ReviewController@index');
    $r->post('/reviews',                 'Public\ReviewController@store');

    $r->get('/about',                    'Public\PageController@about');
    $r->get('/contact',                  'Public\ContactController@index');
    $r->post('/contact',                 'Public\ContactController@store');
    $r->get('/restaurant',               'Public\RestaurantController@index');
    $r->post('/subscribe',               'Public\HomeController@subscribe');
    $r->get('/sitemap.xml',              'Public\SitemapController@index');

    /* -------------------- Booking + payments -------------------- */
    $r->get('/booking/{slug}',           'Public\BookingController@create');
    $r->post('/booking/{slug}',          'Public\BookingController@store');
    $r->get('/booking/{ref}/pay',        'Public\BookingController@pay');
    $r->post('/booking/{ref}/pay',       'Public\PaymentController@process');
    $r->get('/booking/{ref}/confirmation','Public\BookingController@confirmation');
    // Guest folio: in-house charges + online balance settlement
    $r->get('/booking/{ref}/billing',    'Public\BillingController@show');
    $r->post('/booking/{ref}/billing/pay','Public\BillingController@pay');
    // Gateway callbacks / webhooks
    $r->any('/payment/xendit/webhook',   'Public\PaymentController@xenditWebhook');
    $r->get('/payment/return/{ref}',     'Public\PaymentController@genericReturn');

    /* -------------------- Admin auth -------------------- */
    $r->get('/admin/login',              'Admin\AuthController@showLogin');
    $r->post('/admin/login',             'Admin\AuthController@login');
    $r->post('/admin/logout',            'Admin\AuthController@logout');

    /* -------------------- Admin (auth required) -------------------- */
    $r->get('/admin',                    'Admin\DashboardController@index', ['auth']);

    $r->get('/admin/bookings',           'Admin\BookingController@index',  ['auth', 'permission:bookings.view']);
    $r->get('/admin/bookings/{id}',      'Admin\BookingController@show',   ['auth', 'permission:bookings.view']);
    $r->post('/admin/bookings/{id}',     'Admin\BookingController@update', ['auth', 'permission:bookings.manage']);
    $r->post('/admin/bookings/{id}/delete', 'Admin\BookingController@destroy', ['auth', 'permission:bookings.manage']); // super admin only (enforced in controller)
    // Folio: in-house charges + cash settlement
    $r->post('/admin/bookings/{id}/charges',            'Admin\FolioController@addCharge',   ['auth', 'permission:bookings.manage']);
    $r->post('/admin/bookings/{id}/charges/{cid}/void', 'Admin\FolioController@voidCharge',   ['auth', 'permission:bookings.manage']);
    $r->post('/admin/bookings/{id}/settle',             'Admin\FolioController@settleCash',   ['auth', 'permission:bookings.manage']);

    $r->get('/admin/rooms',              'Admin\RoomController@index',     ['auth', 'permission:rooms.view']);
    $r->get('/admin/rooms/{id}',         'Admin\RoomController@edit',      ['auth', 'permission:rooms.manage']);
    $r->post('/admin/rooms/{id}',        'Admin\RoomController@update',    ['auth', 'permission:rooms.manage']);

    $r->get('/admin/housekeeping',       'Admin\HousekeepingController@index', ['auth', 'permission:housekeeping.view']);
    $r->post('/admin/housekeeping/{id}', 'Admin\HousekeepingController@update', ['auth', 'permission:housekeeping.manage']);

    // Generic content CRUD (services, packages, offers)
    $r->get('/admin/{entity:services|packages|offers}',          'Admin\ContentController@index',  ['auth']);
    $r->get('/admin/{entity:services|packages|offers}/{id}',     'Admin\ContentController@edit',   ['auth']);
    $r->post('/admin/{entity:services|packages|offers}/{id}',    'Admin\ContentController@save',   ['auth']);

    $r->get('/admin/reviews',            'Admin\ReviewController@index',   ['auth', 'permission:reviews.moderate']);
    $r->post('/admin/reviews/{id}',      'Admin\ReviewController@update',  ['auth', 'permission:reviews.moderate']);

    $r->get('/admin/restaurant',         'Admin\RestaurantController@index', ['auth', 'permission:restaurant.manage']);
    $r->post('/admin/restaurant/{id}',   'Admin\RestaurantController@save',  ['auth', 'permission:restaurant.manage']);

    $r->get('/admin/payments',           'Admin\PaymentController@index',  ['auth', 'permission:payments.view']);

    // Billing — front-desk folio overview for checkouts & settlements
    $r->get('/admin/billing',              'Admin\BillingController@index',    ['auth', 'permission:bookings.view']);
    $r->post('/admin/billing/{id}/settle',   'Admin\BillingController@settle',   ['auth', 'permission:bookings.manage']);
    $r->post('/admin/billing/{id}/checkout', 'Admin\BillingController@checkout', ['auth', 'permission:bookings.manage']);

    // Accounting
    $r->get('/admin/accounting',                 'Admin\AccountingController@overview', ['auth', 'permission:accounting.view']);
    $r->get('/admin/accounting/ledger',          'Admin\AccountingController@ledger',   ['auth', 'permission:accounting.view']);
    $r->post('/admin/accounting/ledger/record',  'Admin\AccountingController@recordPayment', ['auth', 'permission:accounting.manage']);
    $r->get('/admin/accounting/expenses',        'Admin\AccountingController@expenses', ['auth', 'permission:accounting.view']);
    $r->post('/admin/accounting/expenses/{id}/delete', 'Admin\AccountingController@deleteExpense', ['auth', 'permission:accounting.manage']);
    $r->post('/admin/accounting/expenses/{id}',  'Admin\AccountingController@saveExpense', ['auth', 'permission:accounting.manage']);
    $r->get('/admin/accounting/refunds',         'Admin\AccountingController@refunds',  ['auth', 'permission:accounting.view']);
    $r->post('/admin/accounting/refunds',        'Admin\AccountingController@saveRefund', ['auth', 'permission:accounting.manage']);
    $r->get('/admin/accounting/reports',         'Admin\AccountingController@reports',  ['auth', 'permission:accounting.view']);
    $r->get('/admin/accounting/export/{type}',   'Admin\AccountingController@export',   ['auth', 'permission:accounting.view']);
    $r->get('/admin/accounting/invoice/{id}',    'Admin\AccountingController@invoice',  ['auth', 'permission:accounting.view']);

    $r->get('/admin/users',              'Admin\UserController@index',  ['auth', 'permission:users.manage']);
    $r->get('/admin/users/{id}',         'Admin\UserController@edit',   ['auth', 'permission:users.manage']);
    $r->post('/admin/users/{id}',        'Admin\UserController@save',   ['auth', 'permission:users.manage']);

    $r->get('/admin/settings',           'Admin\SettingsController@index', ['auth', 'permission:settings.manage']);
    $r->post('/admin/settings',          'Admin\SettingsController@save',  ['auth', 'permission:settings.manage']);
};
