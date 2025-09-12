$(function() {
    $('input.form-control').on('input blur', function () {
        if ($(this).val()) {
            console.log(23);
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });
})