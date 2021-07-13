/**
 * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

 define([
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/select-shipping-address',
    'underscore'
], function (addressConverter, checkoutData, selectShippingAddress, _) {
    'use strict';
    
    var timer;

    var mixin = {
        hasChanged: function () {
            if (this.inputName === 'city') {
                clearTimeout(timer);
                timer = setTimeout(function () { 
                    var address = addressConverter.formAddressDataToQuoteAddress(checkoutData.getShippingAddressFromData());
                    selectShippingAddress(address);
                }, 300);
            }

            return this._super();
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
