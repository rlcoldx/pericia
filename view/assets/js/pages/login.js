$(document).ready(function () {

    function appBaseUrl() {
        var fromBody = $('body').data('domain');
        if (fromBody) {
            return String(fromBody).replace(/\/$/, '');
        }
        // Fallback: origin + pasta antes de /login (ex.: /pericia)
        var path = window.location.pathname || '';
        var idx = path.toLowerCase().indexOf('/login');
        if (idx > 0) {
            return window.location.origin + path.substring(0, idx).replace(/\/$/, '');
        }
        return window.location.origin;
    }

    $("#login-form").submit(function (c) {
        $('.login-load').show();
        c.preventDefault();
        var base = appBaseUrl();
        var form = $(this);
        $.ajax({
            type: "POST",
            async: true,
            data: form.serialize(),
            url: base + '/login/sign',
            xhrFields: { withCredentials: true },
            success: function (data) {
                $('#info-login').hide();
                if (data == "1") {
                    window.location.href = base + '/';
                } else {
                    $('button[type="submit"]').prop("disabled", false);
                    $('#info-login').show();
                    $('.login-load').hide();
                }
            },
            error: function () {
                $('button[type="submit"]').prop("disabled", false);
                $('#info-login').show();
                $('.login-load').hide();
            }
        });
    });

    $("#recover-form").submit(function (event) {
        $('.login-load').show();
        $('.error-resete').html('');
        event.preventDefault();
        var base = appBaseUrl();
        let form = $(this);
        $.ajax({
            type: "POST", async: true, data: form.serialize(),
            url: base + '/login/recover-send',
            xhrFields: { withCredentials: true },
            success: function (data) {
                let result = JSON.parse(data);
                if (result.error === false) {
                    $('button[type="submit"]').prop("disabled", false);
                    $('.error-resete').html('<div class="alert alert-success text-center">' + result.message + '</div>');
                    $('.login-load').hide();
                    setTimeout(function() { window.location.href = base + '/login'}, 3000);
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
