<?php

namespace App\Http\Controllers\Api\V2\Register;

use App\Http\Controllers\Api\Controller;
use App\Models\User as UserModel;
use App\Models\UserChallenge as UserChallengeModel;
use App\Models\UserCredential as UserCredentialModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class Init
{
    public function __construct(
        protected PublicKeyCredentialRpEntity $relayingParty,
        protected SerializerInterface $serializer,
        protected \Illuminate\Log\LogManager $logger,
    ) {
    }

    public function __invoke()
    {
        $emailAddress = "mail@mikegsmith.co.uk";

        $user = UserModel::where("email", $emailAddress)->first();

        if (null === $user) {
            abort(404);
        }

        $challenge = random_bytes(32);

        $excludedPublicKeyDescriptors = UserCredentialModel::where("user_id", $user->id)->get()->map(function(UserCredentialModel $credential) {
            return $credential->toPublicKeyCredentialSource()->getPublicKeyCredentialDescriptor();
        })->all();

        $creationOptions = PublicKeyCredentialCreationOptions::create(
            $this->relayingParty,
            $user->toPublicKeyCredentialUserEntity(),
            $challenge,
            excludeCredentials: $excludedPublicKeyDescriptors,
        );

        $json = $this->serializer->serialize(
            $creationOptions,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ],
        );

        UserChallengeModel::create([
            "user_id" => $user->id,
            "challenge_data" => bin2hex($challenge),
        ]);

        return JsonResponse::fromJsonString($json);
    }
}
