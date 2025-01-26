<!DOCTYPE HTML>
<html>
    <head>
        <script>
            function arrayBufferToBase64(buffer) {
                let binary = '';
                let bytes = new Uint8Array(buffer);
                let len = bytes.byteLength;
                for (let i = 0; i < len; i++) {
                    binary += String.fromCharCode( bytes[ i ] );
                }
                return window.btoa(binary);
            }

            function recursiveBase64StrToArrayBuffer(obj) {
                let prefix = '=?BINARY?B?';
                let suffix = '?=';

                if (typeof obj === 'object') {
                    for (let key in obj) {
                        if (typeof obj[key] === 'string') {
                            let str = obj[key];
                            if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                                str = str.substring(prefix.length, str.length - suffix.length);

                                let binary_string = window.atob(str);
                                let len = binary_string.length;
                                let bytes = new Uint8Array(len);
                                for (let i = 0; i < len; i++)        {
                                    bytes[i] = binary_string.charCodeAt(i);
                                }
                                obj[key] = bytes.buffer;
                            }
                        } else {
                            recursiveBase64StrToArrayBuffer(obj[key]);
                        }
                    }
                }


                return obj;
            }

            document.addEventListener("DOMContentLoaded", () => {
                const registerButton = document.getElementById("js-register");

                registerButton.addEventListener("click", () => {
                    window.fetch("http://localhost:8000/api/register/init")
                        .then(response => {
                            if (!response.ok) {
                                throw new Error("Unable to init");
                            }
                            return response.json();
                        })
                        .then(json => recursiveBase64StrToArrayBuffer(json))
                        .then(json => {
                            console.log(json);
                            return json;
                        })
                        .then(json => navigator.credentials.create(json))
                        .then(credential => {
                            console.log(credential);
                            return credential;
                        })
                        .then(credential => {
                            const authenticatorAttestationResponse = {
                                id: credential.id,
                                type: credential.type,
                                rawId: credential.rawId,
                                response: {
                                    clientDataJSON: credential.response.clientDataJSON  ? arrayBufferToBase64(credential.response.clientDataJSON) : null,
                                    attestationObject: credential.response.attestationObject ? arrayBufferToBase64(credential.response.attestationObject) : null,
                                },
                                transports: credential.response.getTransports  ? credential.response.getTransports() : null,
                            };

                            return window.fetch("http://localhost:8000/api/register/verify", {
                                method: "POST",
                                body: JSON.stringify(authenticatorAttestationResponse),
                                cache: "no-cache",
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error("Unable to verify");
                                }
                                return response.json();
                            })
                            .then(json => {
                                if (!json.success) {
                                    throw new Error("User could not be registered");
                                }

                                // registration successful
                                alert("Registration successful");
                            })
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