<?php

namespace App\Http\Controllers\Api\V1\Register;

use App\Http\Controllers\Api\Controller;
use App\Models\User as UserModel;
use App\Models\UserChallenge as UserChallengeModel;
use App\Models\UserCredential as UserCredentialModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use lbuchs\WebAuthn\Binary\ByteBuffer;


class Verify extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->json()->all();
        $this->logger->info("Received data", $request->json()->all());

        $emailAddress = "mail@mikegsmith.co.uk";

        $user = UserModel::where("email", $emailAddress)->latest("updated_at")->first();

        if (null === $user) {
            $this->logger->error("User not found");
            abort(404);
        }

        $userChallenge = UserChallengeModel::where("user_id", $user->id)->latest("updated_at")->first();

        if (null === $userChallenge) {
            $this->logger->error("In progress challenge not found");

            return new JsonResponse([
                "success" => false,
            ]);
        }

        try {
            $response = $this->webAuthnClient->processCreate(
                base64_decode($data["response"]["clientDataJSON"]),
                base64_decode($data["response"]["attestationObject"]),
                ByteBuffer::fromHex($userChallenge->challenge_data),
            );

        } catch (\Exception $exception) {
            $this->logger->error("Error occurred", [
                "exception" => [
                    "message" => $exception->getMessage(),
                ]
            ]);

            return new JsonResponse([
                "success" => false,
            ]);
        }

        $this->logger->info("Credentials valid", [
            "attestation_format" => $response->attestationFormat,
            "credential_id" => $response->credentialId,
            "credential_public_key" => $response->credentialPublicKey,
            "root_valid" => $response->rootValid,
            "user_present" => $response->userPresent,
            "user_verified" => $response->userVerified,
        ]);
    
        return new JsonResponse([
            "success" => true,
        ]);
    }
}
