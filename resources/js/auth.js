$(function() {
    $('input.form-control').on('input blur', function () {
        if ($(this).val()) {
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });
})