jQuery(document).ready(function($){
	

    if($(".wcpma_filter_select").length){
      $(".wcpma_filter_select").wcpma_select2({});
    }

    $('#wcpma_payment_method_product_types').change(function(){
      $('#wcpma_payment_method_products').val(null).trigger("change")
    });

    
});

function wcpma_formatRepo (repo) {
    if (repo.loading) return repo.text;

    var markup = '<div class="clearfix">' +
    '<div class="col-sm-1">' +
    '' +
    '</div>' +
    '<div clas="col-sm-10">' +
    '<div class="clearfix">' +
    '<div class="col-sm-6">' + repo.name + '</div>' +
    '</div>';


    markup += '</div></div>';

    return markup;
}

function wcpma_formatRepoSelection (repo) {
    return repo.name || repo.text;
}