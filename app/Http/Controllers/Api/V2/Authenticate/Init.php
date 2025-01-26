<?php

namespace App\Http\Controllers\Api\V2\Authenticate;

use App\Models\User as UserModel;
use App\Models\UserChallenge as UserChallengeModel;
use App\Models\UserCredential as UserCredentialModel;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class Init
{
    public function __construct(
        protected SerializerInterface $serializer,
        protected \Illuminate\Log\LogManager $logger,
    ) {
    }

    public function __invoke()
    {
        $user = UserModel::where("email", "mail@mikegsmith.co.uk")->first();

        $allowedCredentials = UserCredentialModel::where("user_id", $user->id)->get()->map(function(UserCredentialModel $credential) {
            return $credential->toPublicKeyCredentialSource()->getPublicKeyCredentialDescriptor();
        })->all();

        $challenge = random_bytes(32);

        $options =
            PublicKeyCredentialRequestOptions::create(
                $challenge,
                allowCredentials: $allowedCredentials
            );

        $json = $this->serializer->serialize(
            $options,
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
