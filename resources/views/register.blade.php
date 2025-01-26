<!DOCTYPE HTML>
<html>
    <head>
        <script src="https://unpkg.com/@simplewebauthn/browser/dist/bundle/index.umd.min.js"></script>
        <script>
            const { startAuthentication, startRegistration } = SimpleWebAuthnBrowser;

            document.addEventListener("DOMContentLoaded", () => {
                const authenticateButton = document.getElementById("js-authenticate");
                const registerButton = document.getElementById("js-register");

                authenticateButton.addEventListener("click", () => {
                    window.fetch("http://localhost:8000/api/v2/authenticate/init")
                        .then(response => {
                            if (!response.ok) {
                                throw new Error("Unable to init");
                            }
                            return response.json();
                        })
                        .then(optionsJSON => {
                            console.log(optionsJSON);
                            return optionsJSON;
                        })
                        .then(optionsJSON => startAuthentication({
                            optionsJSON,
                        }))
                        .then(verificationResponse => {
                            console.log(verificationResponse);
                            return verificationResponse;
                        })
                        .then(verificationResponse => {
                            return window.fetch("http://localhost:8000/api/v2/authenticate/verify", {
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(verificationResponse),
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

                                alert("Authentication successful");
                            })
                        .catch(error => {
                            console.log(error);
                        })
                });

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
        <button id="js-authenticate">Authenticate</button>
    </body>
</html>