$(document).ready(function () {

    $("#login-form").submit(function (c) {
        $('.login-load').show();
        c.preventDefault();
        var DOMAIN = $('body').data('domain');
        var form = $(this);
        $.ajax({
            type: "POST", async: true, data: form.serialize(),
            url: DOMAIN + '/login/sign',
            success: function (data) {
                $('#info-login').hide();
                if (data == "1") {
                    window.location.href = DOMAIN+'/';
                } else {
                    $('button[type="submit"]').prop("disabled", false);
                    $('#info-login').show();
                    $('.login-load').hide();
                }
            }
        });
    });

    $("#recover-form").submit(function (event) {
        $('.login-load').show();
        $('.error-resete').html('');
        event.preventDefault();
        var DOMAIN = $('body').data('domain');;
        let form = $(this);
        $.ajax({
            type: "POST", async: true, data: form.serialize(),
            url: DOMAIN + '/login/recover-send',
            success: function (data) {
                let result = JSON.parse(data);
                if (result.error === false) {
                    $('button[type="submit"]').prop("disabled", false);
                    $('.error-resete').html('<div class="alert alert-success text-center">' + result.message + '</div>');
                    $('.login-load').hide();
                    setTimeout(function() { window.location.href = DOMAIN + '/login'}, 3000);
                } else {
                    $('.error-resete').html('<div class="alert alert-danger text-center">' + result.message + '</div>');
                    $('.login-load').hide();
                }
            }
        });
    });

    $("#resenha").keyup(function () {
        if ($(this).val() != $("#senha").val()) {
            $('#info-resenha').show();
        } else {
            $('#info-resenha').hide();
        }
    });

});

window.show_pass = function show_pass(id) {
    var type = $('#' + id + '').attr('data-type');
    if (type == 'hide') {
        $('#' + id + '').attr('type', 'text');
        $('#' + id + '').attr('data-type', 'show');
    } else {
        $('#' + id + '').attr('type', 'password');
        $('#' + id + '').attr('data-type', 'hide');
    }
};