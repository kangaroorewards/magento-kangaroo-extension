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
            KangarooApps.Loyalties.my_account_login = config.baseStoreUrl + "customer/account/login/";
            KangarooApps.Loyalties.my_account_register = config.baseStoreUrl + "customer/account/create/";
            KangarooApps.Loyalties.my_account_page = config.baseStoreUrl + "customer/account/login/";
            KangarooApps.Loyalties.shop = {
                domain: config.baseStoreUrl,
                storeId: config.storeId
            };

            if (config.isProductPage) {
                var product = config.currentProduct;
                product['product'].forEach(
                    function (item) {

                        var productD = {
                            code: item["code"],
                            productId: item["productId"],
                            price: item["price"],
                            title: item["title"]
                        };
                        productDetails.push(productD);
                    }
                );

                KangarooApps.Loyalties.product = {
                    id: product['code'],
                    product: productDetails
                }

            }

            if (config.isCartExist) {
                var cart = config.cart;

                cart['cartItems'].forEach(
                    function (item) {
                        var productItem = {
                            code: item["code"],
                            variant_id: item["productId"],
                            price: item["price"],
                            quantity: item["quantity"]
                        };
                        productList.push(productItem);
                    }
                );

                if (productList !== undefined && productList.length > 0) {
                    KangarooApps.Loyalties.checkout = {
                        total: cart['subtotal'],
                        cart: cart['id'],
                        productList: productList,
                        discount: cart['discount'],
                        subtotal: cart['subtotal']
                    }
                }
            }

            var localData = localStorage.getItem('mage-cache-storage');
            if (localData !== undefined) {
                localData = JSON.parse(localData);
                if (localData['kangaroo-customer'] !== undefined && localData['kangaroo-customer']['customer']['id'] != null) {
                    KangarooApps.Loyalties.customer = {
                        id: localData['kangaroo-customer']['customer']['id'],
                        email: localData['kangaroo-customer']['customer']['email']
                    }
                }
            }


            $(document).on(
                'ajaxComplete', function () {
                    if (KangarooApps.Loyalties.customer === undefined) {
                        localData = localStorage.getItem('mage-cache-storage');
                        if (localData !== undefined) {
                            localData = JSON.parse(localData);
                            if (localData !== undefined) {
                                if (localData['kangaroo-customer'] !== undefined && localData['kangaroo-customer']['customer']['id'] != null) {
                                    KangarooApps.Loyalties.customer = {
                                        id: localData['kangaroo-customer']['customer']['id'],
                                        email: localData['kangaroo-customer']['customer']['email']
                                    };
                                    $.getScript(config.kangarooAPIUrl + "/magento/initJS?domain="+encodeURI(config.baseStoreUrl));
                                }
                            }
                        }
                    }
                }
            );
            $.getScript(config.kangarooAPIUrl + "/magento/initJS?domain="+encodeURI(config.baseStoreUrl));

        }
    }
);
