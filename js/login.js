$(document).ready(function () {

    $("#loginForm").on("submit", function (e) {
        e.preventDefault();

        // Clear previous message
        $("#message").html("");

        // Disable login button while processing
        $("#loginBtn").prop("disabled", true).text("Logging in...");

        $.ajax({
            url: "php/login.php",
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

                    // Redirect to profile page
                    setTimeout(function () {
                        window.location.href = "profile.html";
                    }, 1000);

                } else {

                    $("#message").html(
                        '<div class="alert alert-danger">' +
                        response.message +
                        '</div>'
                    );

                    $("#loginBtn")
                        .prop("disabled", false)
                        .text("Login");
                }
            },

            error: function (xhr) {

                console.log("Login error:", xhr.responseText);

                $("#message").html(
                    '<div class="alert alert-danger">' +
                    'Unable to connect to the server. Please try again.' +
                    '</div>'
                );

                $("#loginBtn")
                    .prop("disabled", false)
                    .text("Login");
            }
        });
    });

});