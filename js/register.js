$(document).ready(function () {

    $("#registerForm").on("submit", function (e) {
        e.preventDefault();

        $("#message").html("");

        $.ajax({
            url: "php/register.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",

            success: function (response) {

                if (response.success) {

                    $("#message").html(
                        '<div class="alert alert-success">' +
                        response.message +
                        '</div>'
                    );

                    $("#registerForm")[0].reset();

                    setTimeout(function () {
                        window.location.href = "login.html";
                    }, 1500);

                } else {

                    $("#message").html(
                        '<div class="alert alert-danger">' +
                        response.message +
                        '</div>'
                    );
                }
            },

            error: function () {

                $("#message").html(
                    '<div class="alert alert-danger">' +
                    'Something went wrong. Please try again.' +
                    '</div>'
                );
            }
        });
    });

});