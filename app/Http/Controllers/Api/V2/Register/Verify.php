<?php

namespace App\Http\Controllers\Api\V2\Register;

use App\Http\Controllers\Api\Controller;
use App\Models\User as UserModel;
use App\Models\UserChallenge as UserChallengeModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class Verify extends Controller
{
    public function __construct(
        protected PublicKeyCredentialRpEntity $relayingParty,
        protected SerializerInterface $serializer,
        protected AuthenticatorAttestationResponseValidator $validator,
        protected \Illuminate\Log\LogManager $logger,
    ) {
    }

    public function __invoke(Request $request)
    {
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

        $data = $request->getContent();
        $this->logger->info("Received data", json_decode($data, true));

        try {
            $publicKeyCredential = $this->serializer->deserialize(
                $data,
                PublicKeyCredential::class,
                'json',
            );
        } catch (\Exception $exception) {
            $this->logger->error("Error occurred deserializing data: " . $exception->getMessage());

            return new JsonResponse([
                "verified" => false,
            ]);
        }


        if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            $this->logger->error("Attestation response could not be verified");

            return new JsonResponse([
                "verified" => false,
            ]);
        }
        
        $userEntity = PublicKeyCredentialUserEntity::create(
            $user->email,
            $user->id,
            $user->name,
        );

        $creationOptions = PublicKeyCredentialCreationOptions::create(
            $this->relayingParty,
            $userEntity,
            hex2bin($userChallenge->challenge_data),
        );

        try {
            $publicKeyCredentialSource = $this->validator->check(
                $publicKeyCredential->response,
                $creationOptions,
                "localhost",
            );
        } catch (\Exception $exception) {
            $this->logger->error("Could not be validated: " . $exception->getMessage());

            return new JsonResponse([
                "verified" => false,
            ]);
        }

        $this->logger->info("Credentials valid", [
            "class" => get_class($publicKeyCredentialSource),
            "attestation_type" => $publicKeyCredentialSource->attestationType,
            "credential_id" => bin2hex($publicKeyCredentialSource->publicKeyCredentialId),
            "credential_public_key" => bin2hex($publicKeyCredentialSource->credentialPublicKey),
            "transports" => $publicKeyCredentialSource->transports,
            "type" => $publicKeyCredentialSource->type,
        ]);

        return new JsonResponse([
            "verified" => true,
        ]);
    }
}
