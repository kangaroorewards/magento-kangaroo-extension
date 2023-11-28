define(
    [
        "jquery"
    ],
    function ($) {
        "use strict";
        return function (config) {
            KangarooApps = KangarooApps || {};
            var productList = [];
            var productDetails = [];
            KangarooApps.Loyalties = KangarooApps.Loyalties || {};
            KangarooApps.Loyalties.version = config.plugin_version;
            KangarooApps.Loyalties.my_account_login = config.baseStoreUrl + "customer/account/login/";
            KangarooApps.Loyalties.my_account_register = config.baseStoreUrl + "customer/account/create/";
            KangarooApps.Loyalties.my_account_page = config.baseStoreUrl + "customer/account/login/";
            KangarooApps.Loyalties.shop = {
                domain: config.baseStoreUrl,
                storeId: config.storeId
            };

            KangarooApps.Loyalties.customer_status = "pending";

            fetch(config.baseStoreUrl + '/rest/V1/kangaroo/customer').then(response => {
                return response.json();
            }).then(data => {
                let responseArray = JSON.parse(data);
                if (typeof responseArray.data !== 'undefined') {
                    KangarooApps.Loyalties.customer = responseArray.data;
                    KangarooApps.Loyalties.customer_status = "retrieved";
                }
            });

            if (config.isProductPage) {
                var product = config.currentProduct;
                if (product != null) {
                    product['product'].forEach(
                        function (item) {

                            var productD = {
                                code: item["code"],
                                parentId: item['parentId'],
                                productId: item["productId"],
                                price: item["price"],
                                title: item["title"],
                                categories: item["categories"]
                            };
                            productDetails.push(productD);
                        }
                    );

                    KangarooApps.Loyalties.product = {
                        id: product['code'],
                        product: productDetails
                    }
                }
            }

            var cart = null;
            if (config.isCartExist) {
                cart = config.cart;
                if (cart != null) {
                    initialLoyaltyCheckout(cart);
                }
            } else {
                fetch(config.baseStoreUrl + '/rest/V1/kangaroo/cart-info').then(response => {
                    return response.json();
                }).then(data => {
                    let responseArray = JSON.parse(data);
                    if (typeof responseArray.data !== 'undefined' && responseArray.data != null) {
                        initialLoyaltyCheckout(responseArray.data);
                    }
                });
            }

            $.getScript(config.kangarooAPIUrl + "/magento/initJS?rc=1&plugin_version=" + config.plugin_version + "&domain=" + encodeURI(config.baseStoreUrl));

            function initialLoyaltyCheckout(cart) {
                cart['cartItems'].forEach(
                    function (item) {
                        var productItem = {
                            code: item["code"],
                            parentId: item["parentId"],
                            variant_id: item["productId"],
                            price: item["price"],
                            quantity: item["quantity"],
                            categories: item["categories"]
                        };
                        productList.push(productItem);
                    }
                );

                if (typeof productList !== 'undefined' && productList.length > 0) {
                    KangarooApps.Loyalties.checkout = {
                        total: cart['subtotal'],
                        cart: cart['id'],
                        productList: productList,
                        discount: cart['discount'],
                        subtotal: cart['subtotal']
                    }
                }
            }
        }
    }
);
