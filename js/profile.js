$(document).ready(function () {

    // Load logged-in user's profile
    loadProfile();


    function loadProfile() {

        $.ajax({
            url: "php/profile.php",
            type: "GET",
            dataType: "json",

            success: function (response) {

                console.log("Profile response:", response);

                if (response.success) {

                    $("#username").val(response.user.username);
                    $("#email").val(response.user.email);

                    if (response.profile) {

                        $("#fullName").val(
                            response.profile.fullName || ""
                        );

                        $("#age").val(
                            response.profile.age || ""
                        );

                        $("#bio").val(
                            response.profile.bio || ""
                        );

                        $("#interests").val(
                            response.profile.interests || ""
                        );
                    }

                } else {

                    $("#message").html(
                        '<div class="alert alert-danger">' +
                        response.message +
                        '</div>'
                    );

                    setTimeout(function () {
                        window.location.href = "login.html";
                    }, 1500);
                }
            },

            error: function (xhr) {

                console.log(
                    "Profile loading error:",
                    xhr.responseText
                );

                $("#message").html(
                    '<div class="alert alert-danger">' +
                    'Unable to load profile.' +
                    '</div>'
                );
            }
        });
    }


    // Save profile
    $("#profileForm").on("submit", function (e) {

        e.preventDefault();

        $("#message").html("");

        $("#saveProfileBtn")
            .prop("disabled", true)
            .text("Saving...");


        $.ajax({
            url: "php/profile.php",
            type: "POST",

            data: {
                fullName: $("#fullName").val(),
                age: $("#age").val(),
                bio: $("#bio").val(),
                interests: $("#interests").val()
            },

            dataType: "json",

            success: function (response) {

                console.log("Save response:", response);

                if (response.success) {

                    $("#message").html(
                        '<div class="alert alert-success">' +
                        response.message +
                        '</div>'
                    );

                } else {

                    $("#message").html(
                        '<div class="alert alert-danger">' +
                        response.message +
                        '</div>'
                    );
                }

                $("#saveProfileBtn")
                    .prop("disabled", false)
                    .text("Save Profile");
            },

            error: function (xhr) {

                console.log(
                    "Profile save error:",
                    xhr.responseText
                );

                $("#message").html(
                    '<div class="alert alert-danger">' +
                    'Unable to save profile. Please try again.' +
                    '</div>'
                );

                $("#saveProfileBtn")
                    .prop("disabled", false)
                    .text("Save Profile");
            }
        });
    });


    // Logout
    $("#logoutBtn").on("click", function () {

        $("#logoutBtn")
            .prop("disabled", true)
            .text("Logging out...");


        $.ajax({
            url: "php/profile.php",
            type: "POST",

            data: {
                action: "logout"
            },

            dataType: "json",

            success: function (response) {

                console.log("Logout response:", response);

                window.location.href = "login.html";
            },

            error: function () {

                // Redirect even if server response fails
                window.location.href = "login.html";
            }
        });
    });

});