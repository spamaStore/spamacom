/** Enhanced E-Commerce Tracking Module v2.2.1 -- https://vanstudio.co.ua -- */

var ee_start = 0;
var ee_generate = 0;

function setIntervalEE(callback, global, generate) {
    var x = 0;
    var intervalEE = window.setInterval(function () {
        if ((global && ee_start) || (!global && document.cookie.match('(?:^|;)\\s*(_ga=|__utma=|_eecid=)([^;]*)'))) {
            callback();
            window.clearInterval(intervalEE);
        } else if (++x > 5) {
            if (generate) {
                setTimeout(function() {
                    callback();
                }, ee_generate);
                ee_generate = ee_generate + 1000;
            }
            window.clearInterval(intervalEE);
        }
    }, 1000);
}

var ee_product = {
    'click': function (product_id, position, type) {
        $.ajax({
            url: 'index.php?route=extension/module/ee_tracking/click',
            type: 'post',
            data: { 'product_id': product_id, 'position': position, 'type': type, 'url': window.location.href, 'title': document.title },
            dataType: 'json',
            success: function(json) {
                if (json) {
                    console.log(json);
                }
            },
            error: function(xhr, exc, error) {
                $.post('index.php?route=extension/module/ee_tracking/clicklog',
                    { 'error': error + ' (exc: ' + exc + ' status: ' + xhr.statusText + ')', 'url': window.location.href }, function( logs ) {
                        if (logs) {
                            console.log(logs);
                        }
                    });
            }
        });
    }
}

var ee_promotion = {
    'click': function (banner_id, position) {
        $.ajax({
            url: 'index.php?route=extension/module/ee_tracking/promotionclick',
            type: 'post',
            data: { 'banner_id': banner_id, 'position': position, 'url': window.location.href, 'title': document.title },
            dataType: 'json',
            success: function(json) {
                if (json) {
                    console.log(json);
                }
            },
            error: function(xhr, exc, error) {
                $.post('index.php?route=extension/module/ee_tracking/promotionlog',
                    { 'error': error + ' (exc: ' + exc + ' status: ' + xhr.statusText + ')', 'url': window.location.href }, function( logs ) {
                        console.log(logs);
                    });
            }
        });
    }
}

$(document).ready(function() {
    $(document).on('click', '[onclick*=\'cart.add\']', function(e) {
        var ee_data = $(this).attr('onclick').replace(/'/g, '').replace('cart.add(', '').replace(');', '').split(',');
        if (ee_data[0]) {
            $.ajax({
                url: 'index.php?route=extension/module/ee_tracking/quickaddtocart',
                type: 'post',
                data: { 'product_id': ee_data[0], 'quantity': ee_data[1], 'type': $(this).data('eet-type'), 'position': $(this).data('eet-position'), 'url': window.location.href, 'title': document.title },
                dataType: 'json',
                success: function(json) {
                    if (json) {
                        console.log(json);
                    }
                },
                error: function(xhr, exc, error) {
                    $.post('index.php?route=extension/module/ee_tracking/addtocartlog',
                        { 'error': error + ' (exc: ' + exc + ' status: ' + xhr.statusText + ')', 'url': window.location.href }, function( logs ) {
                            if (logs) {
                                console.log(logs);
                            }
                        });
                }
            });
        }
    });

    $(document).on('click', '[onclick*=\'addToCart\']', function(e) {
        var ee_data = $(this).attr('onclick').replace(/'/g, '').replace('addToCart(', '').replace(');', '').split(',');
        if (ee_data[0]) {
            $.ajax({
                url: 'index.php?route=extension/module/ee_tracking/quickaddtocart',
                type: 'post',
                data: { 'product_id': ee_data[0], 'quantity': ee_data[1], 'type': $(this).data('eet-type'), 'position': $(this).data('eet-position'), 'url': window.location.href, 'title': document.title },
                dataType: 'json',
                success: function(json) {
                    if (json) {
                        console.log(json);
                    }
                },
                error: function(xhr, exc, error) {
                    $.post('index.php?route=extension/module/ee_tracking/addtocartlog',
                        { 'error': error + ' (exc: ' + exc + ' status: ' + xhr.statusText + ')', 'url': window.location.href }, function( logs ) {
                            if (logs) {
                                console.log(logs);
                            }
                        });
                }
            });
        }
    });

    $('#button-cart').on('click', function() {
        $.ajax({
            url: 'index.php?route=extension/module/ee_tracking/addtocart',
            type: 'post',
            data: { 'product_id': $('input[name=\'product_id\']').val(), 'quantity': $('input[name=\'quantity\']').val(), 'type': 'product', 'option': $("[name^=\'option\']").serializeArray(), 'url': window.location.href, 'title': document.title },
            dataType: 'json',
            success: function(json) {
                if (json) {
                    console.log(json);
                }
            },
            error: function(xhr, exc, error) {
                $.post('index.php?route=extension/module/ee_tracking/addtocartlog',
                    { 'error': error + ' (exc: ' + exc + ' status: ' + xhr.statusText + ')', 'url': window.location.href }, function( logs ) {
                        if (logs) {
                            console.log(logs);
                        }
                    });
            }
        });
    });

    if ($.inArray($.trim(document.location.pathname + document.location.search),['false']) !== -1) {
        setIntervalEE(function() {
            $.ajax({
                url: 'index.php?route=extension/module/ee_tracking/checkout',
                type: 'post',
                data: { 'step': 1, 'step_option': 'custom checkout', 'url': window.location.href, 'title': document.title },
                dataType: 'json',
                success: function(json) {
                    if (json) {
                        console.log(json);
                    }
                },
                error: function(xhr, exc, error) {
                    $.post('index.php?route=extension/module/ee_tracking/checkoutlog',
                        { 'error': error + ' (exc: ' + exc + ' status: ' + xhr.statusText + ')', 'url': window.location.href }, function( logs ) {
                            if (logs) {
                                console.log(logs);
                            }
                        });
                }
            });
        }, 0, 0);
    }
});