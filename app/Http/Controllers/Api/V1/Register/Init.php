<?php

namespace App\Http\Controllers\Api\V1\Register;

use App\Http\Controllers\Api\Controller;
use App\Models\User as UserModel;
use App\Models\UserChallenge as UserChallengeModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Init extends Controller
{
    public function __invoke()
    {
        $emailAddress = "mail@mikegsmith.co.uk";

        $user = UserModel::where("email", $emailAddress)->first();

        if (null === $user) {
            abort(404);
        }

        $data = $this->webAuthnClient->getCreateArgs(
            $user->id,
            $user->email,
            $user->name,
        );

        UserChallengeModel::create([
            "user_id" => $user->id,
            "challenge_data" => $this->webAuthnClient->getChallenge(),
        ]);

        $data = json_decode(json_encode($data), true);
        
        $this->logger->info("Authentication request data", $data);

        return new JsonResponse($data);
    }
}
