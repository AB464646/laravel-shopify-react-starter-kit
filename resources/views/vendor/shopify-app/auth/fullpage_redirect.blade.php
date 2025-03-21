<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <base target="_top">
        <meta name="shopify-api-key" content="{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name ) }}"/>
        <!-- <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script> -->

        <title>Redirecting...</title>

        <script src="{{config('shopify-app.appbridge_cdn_url') ?? 'https://unpkg.com'}}/@shopify/app-bridge{!! $appBridgeVersion !!}"></script>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var redirectUrl = "{!! $authUrl !!}";
                var normalizedLink;
                if (window.top === window.self) {
                    // If the current window is the 'parent', change the URL by setting location.href
                    window.top.location.href = redirectUrl;
                } else {
                    // If the current window is the 'child', change the parent's URL with postMessage
                    normalizedLink = document.createElement('a');
                    normalizedLink.href = redirectUrl;

                    var AppBridge = window['app-bridge'];
                    var createApp = AppBridge.default;
                    var Redirect = AppBridge.actions.Redirect;
                    var app = createApp({
                        apiKey: "{{ $apiKey }}",
                        host: "{{ $host }}",
                    });

                    var redirect = Redirect.create(app);
                    redirect.dispatch(Redirect.Action.REMOTE, normalizedLink.href);
                }
            });
        </script>
    </head>
    <body>
    </body>
</html>
