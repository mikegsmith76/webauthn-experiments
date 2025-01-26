<!DOCTYPE HTML>
<html>
    <head>
        <script src="https://unpkg.com/@simplewebauthn/browser/dist/bundle/index.umd.min.js"></script>
        <script>
            const { startRegistration } = SimpleWebAuthnBrowser;

            document.addEventListener("DOMContentLoaded", () => {
                const registerButton = document.getElementById("js-register");

                registerButton.addEventListener("click", () => {
                    window.fetch("http://localhost:8000/api/v2/register/init")
                        .then(response => {
                            if (!response.ok) {
                                throw new Error("Unable to init");
                            }
                            return response.json();
                        })
                        .then(options => {
                            console.log(options);
                            return options;
                        })
                        .then(options => startRegistration({
                            optionsJSON: options,
                        }))
                        .then(attestationResponse => {
                            console.log(attestationResponse);
                            return attestationResponse;
                        })
                        .then(attestationResponse => {
                            return window.fetch("http://localhost:8000/api/v2/register/verify", {
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(attestationResponse),
                                cache: "no-cache",
                            })
                        })
                        .then(response => {
                                if (!response.ok) {
                                    throw new Error("Unable to verify");
                                }
                                return response.json();
                            })
                            .then(json => {
                                if (!json.verified) {
                                    throw new Error("User could not be registered");
                                }

                                // registration successful
                                alert("Registration successful");
                            })
                        .catch(error => {
                            console.log(error);
                        })
                });
            });
        </script>
    </head>
    <body>
        <button id="js-register">Register</button>
    </body>
</html>