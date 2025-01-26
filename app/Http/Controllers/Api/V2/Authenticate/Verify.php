<?php

namespace App\Http\Controllers\Api\V2\Authenticate;

use App\Models\User as UserModel;
use App\Models\UserChallenge as UserChallengeModel;
use App\Models\UserCredential as UserCredentialModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;

class Verify
{
    public function __construct(
        protected SerializerInterface $serializer,
        protected AuthenticatorAssertionResponseValidator $validator,
        protected PublicKeyCredentialRpEntity $relayingParty,
        protected \Illuminate\Log\LogManager $logger
    ) {}

    public function __invoke(Request $request)
    {
        $data = $request->getContent();
        $this->logger->info("Received data", json_decode($data, true));

        try {
            $publicKeyCredential = $this->serializer->deserialize($data, PublicKeyCredential::class, 'json');

        } catch (\Exeption $exception) {
            $this->logger->error("Could not be validated: " . $exception->getMessage());

            return new JsonResponse([
                "verified" => false,
            ]);
        }

        if (!$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return new JsonResponse([
                "verified" => false,
            ]);
        }

        $this->logger->info("Attempting to fetch credential with id: " . $publicKeyCredential->rawId);

        $userCredential = UserCredentialModel::byRawCredentialId($publicKeyCredential->rawId)->first();

        if (null === $userCredential) {
            $this->logger->error("Credentials could not be found");

            return new JsonResponse([
                "verified" => false,
            ]);
        }

        $userChallenge = UserChallengeModel::where("user_id", $userCredential->user_id)->latest("updated_at")->first();

        if (null === $userChallenge) {
            $this->logger->error("In progress challenge not found");

            return new JsonResponse([
                "success" => false,
            ]);
        }

        $this->logger->info("Generating credential creation options");

        $publicKeyCredentialRequestOptions =
            PublicKeyCredentialRequestOptions::create(
                hex2bin($userChallenge->challenge_data),
                allowCredentials: [
                    $userCredential->toPublicKeyCredentialSource()->getPublicKeyCredentialDescriptor(),
                ],
            );

        $this->logger->info("Validating credentials with " . get_class($this->validator));

        try {
            $publicKeyCredentialSource = $this->validator->check(
                $userCredential->toPublicKeyCredentialSource(),
                $publicKeyCredential->response,
                $publicKeyCredentialRequestOptions,
                "localhost",
                $userCredential->user_id,
            );
        } catch (\Exception $exception) {
            $this->logger->error("Validation error", [
                "exception" => [
                    "message" => $exception->getMessage(),
                    "type" => get_class($exception),
                ],
            ]);

            return new JsonResponse([
                "success" => false,
            ]);
        }

        return new JsonResponse([
            "verified" => true,
        ]);
    }
}
