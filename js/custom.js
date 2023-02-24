jQuery(document).ready(function($) {
    $("#autocomplete_product").autocomplete({
        source: function(req, response){
            // var $tax_id     = $('input[name="taxonomy_id"]').val();
            $.getJSON(AJAX.ajax_url+'?callback=?&action=autocomplete_action', req, response);
        },
        create: function() {
            $(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
                console.log(item);
                return $( "<li>" ).append( "<div>" + item.product + "</div>" ).appendTo( ul );
            }
        },
        select: function(event, ui) {
            console.log(ui);
            // $(".owner_country").append("<span data-first_price=" + ui.item.first_price + " data-next_price=" + ui.item.next_price + " >" + ui.item.country + "<b class='delete'>x</b></span");
            var product = $('input[name="product"]');

            product.val(ui.item.id);
            $("input#autocomplete_product").hide();
            $('.product_label').html(ui.item.product).show();

            // Neu co truong gia tien thi dien vao luon
            if ($('input[name="price"]').length) {
                $('input[name="price"]').val(ui.item.price);
                $('input[name="single_price"]').val(ui.item.price);
            }
        },
        minLength: 0,
    });

    $('input[type="checkbox"]').click( function () {
        var numberOfChecked = $('input:checkbox:checked').length;
        var single_price = $('input[name="single_price"]').val();

        $.ajax({
            type: "POST",
            url: AJAX.ajax_url,
            data: {
              action: "calculate_price",
              quantity: numberOfChecked,
              price: single_price
            },
            error: function (xhr, ajaxOptions, thrownError) {
              console.log(xhr.status);
              console.log(xhr.responseText);
              console.log(thrownError);
            },
            success: function (resp) {
                $('input[name="price"]').val(resp);
            },
        });
    });

    $('input[name="price"]').change( function() {
        var numberOfChecked = $('input:checkbox:checked').length;
        var total = $(this).val();

        if (numberOfChecked <= 1) {
            $('input[name="single_price"]').val(total);
        } else {
            $('input[name="single_price"]').val(total / numberOfChecked);
        }
    });
});