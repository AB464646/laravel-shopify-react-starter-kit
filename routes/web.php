<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::group(['middleware' => ['verify.shopify']], function () {

    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'auth' => [
                'user' => Auth::user(),
            ],
        ]);
    })->name('home');

    Route::get('/dashboard', [ProductController::class, 'index'])->name('dashboard');
    Route::get('/Orderspage', function () {
        return Inertia::render('Orderspage');
    })->name('Orderspage');
    Route::get('/ProductPage', function () {
        return Inertia::render('ProductPage');
    })->name('ProductPage');
    Route::post('/createProduct', [ProductController::class, 'createShopifyProduct'])->name('product.create');
    Route::get('/getProducts', [ProductController::class, 'getProducts'])->name('product.get');
    Route::get('/syncProducts', [ProductController::class, 'syncProducts'])->name('product.sync');
    Route::get('/getOrders', [OrderController::class, 'getOrders'])->name('orders.get');
    Route::post('/updateStatus/{id}', [ProductController::class, 'updateProductStatus'])->name('product.updateStatus');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    });
    Route::get('orders', function () {
        $user = Auth::user();
        $query = <<<QUERY
            query {
                order(id: "gid://shopify/Order/6098811846904") {
                    fulfillmentOrders(first: 250) {
                        nodes {
                            id
                            requestStatus
                            status
                            lineItems(first: 250) {
                                nodes {
                                    id
                                    totalQuantity
                                }
                            }
                            fulfillBy
                        }
                    }
                }
            }
        QUERY;
        // $result = $user->api()->graph($query);
        // "gid://shopify/FulfillmentOrder/7205766889720"
        // "gid://shopify/FulfillmentOrderLineItem/15074270380280"
        $query = $query = <<<QUERY
            mutation fulfillmentCreate(\$fulfillment: FulfillmentV2Input!, \$message: String) {
                fulfillmentCreateV2(fulfillment: \$fulfillment, message: \$message) {
                    fulfillment {
                        fulfillmentLineItems(first: 10) {
                            edges {
                                node {
                                    id
                                    lineItem {
                                        title
                                        variant {
                                            id
                                        }
                                    }
                                    quantity
                                    originalTotalSet {
                                        shopMoney {
                                            amount
                                            currencyCode
                                        }
                                    }
                                }
                            }
                        }
                        status
                        estimatedDeliveryAt
                        location {
                            id
                            legacyResourceId
                        }
                        service {
                            handle
                        }
                        trackingInfo(first: 10) {
                            company
                            number
                            url
                        }
                        originAddress {
                            address1
                            address2
                            city
                            countryCode
                            provinceCode
                            zip
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        QUERY;
        $variable = [
            "fulfillment" => [
                "lineItemsByFulfillmentOrder" => [
                    "fulfillmentOrderId" => "gid://shopify/FulfillmentOrder/7205766889720",
                ],
            ],
        ];
        $result = $user->api()->graph($query, $variable);
        dd($result);
    });

});

require __DIR__ . '/auth.php';
