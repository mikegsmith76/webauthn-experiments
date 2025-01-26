<?php

namespace App\Providers;

use App\Http\Controllers\Api\V2\Register\Init as V2RegisterInitController;
use App\Http\Controllers\Api\V2\Register\Verify as V2RegisterVerifyController;

use App\Http\Controllers\Api\V2\Authenticate\Init as V2AuthenticateInitController;
use App\Http\Controllers\Api\V2\Authenticate\Verify as V2AuthenticateVerifyController;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use lbuchs\WebAuthn\WebAuthn;

use Symfony\Component\Serializer\SerializerInterface;

use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $app = $this->app;

        $this->app->bind(WebAuthn::class, function(Application $app) {
            return new Webauthn(
                config("app.rp_name"),
                config("app.rp_id"),
            );
        });


        $this->app->bind(PublicKeyCredentialRpEntity::class, function(Application $app) {
            return new PublicKeyCredentialRpEntity(
                config("app.rp_name"),
                config("app.rp_id"),
            );
        });

        $this->app->bind(AuthenticatorAttestationResponseValidator::class, function(Application $app) {
            $factory = $app->make(CeremonyStepManagerFactory::class);
            $factory->setSecuredRelyingPartyId(["localhost"]);
            
            return new AuthenticatorAttestationResponseValidator(
                $factory->creationCeremony(),
            );
        });

        $this->app->bind(AuthenticatorAssertionResponseValidator::class, function(Application $app) {
            $factory = $app->make(CeremonyStepManagerFactory::class);
            $factory->setSecuredRelyingPartyId(["localhost"]);
            
            return new AuthenticatorAssertionResponseValidator(
                $factory->requestCeremony(),
            );
        });

        $this->app
            ->when(V2RegisterInitController::class)
            ->needs(SerializerInterface::class)
            ->give(function() use ($app) {
                $manager = $app->make(AttestationStatementSupportManager::class);
                return (new WebauthnSerializerFactory($manager))->create();
            });

        $this->app
            ->when(V2RegisterVerifyController::class)
            ->needs(SerializerInterface::class)
            ->give(function() use ($app) {
                $manager = $app->make(AttestationStatementSupportManager::class);
                return (new WebauthnSerializerFactory($manager))->create();
            });

        $this->app
            ->when(V2AuthenticateInitController::class)
            ->needs(SerializerInterface::class)
            ->give(function() use ($app) {
                $manager = $app->make(AttestationStatementSupportManager::class);
                return (new WebauthnSerializerFactory($manager))->create();
            });

            $this->app
            ->when(V2AuthenticateVerifyController::class)
            ->needs(SerializerInterface::class)
            ->give(function() use ($app) {
                $manager = $app->make(AttestationStatementSupportManager::class);
                return (new WebauthnSerializerFactory($manager))->create();
            });
    }
}
