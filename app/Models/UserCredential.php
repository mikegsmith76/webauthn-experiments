<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EmptyTrustPath;

class UserCredential extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByRawCredentialId(EloquentBuilder $query, string $rawId): EloquentBuilder
    {
        return $query->where("credential_id", Base64UrlSafe::encodeUnpadded($rawId));
    }

    public static function fromPublicKeyCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, User $user): self
    {
        $model = new self();

        $model->credential_id = Base64UrlSafe::encodeUnpadded($publicKeyCredentialSource->publicKeyCredentialId);
        $model->credential_type = $publicKeyCredentialSource->type;
        $model->transports = json_encode($publicKeyCredentialSource->transports);
        $model->attestation_type = $publicKeyCredentialSource->attestationType;
        $model->trust_path = json_encode($publicKeyCredentialSource->trustPath instanceof EmptyTrustPath ? [] : $publicKeyCredentialSource->trustPath->certificates);
        $model->aaguid = $publicKeyCredentialSource->aaguid->toString();
        $model->public_key = Base64UrlSafe::encodeUnpadded($publicKeyCredentialSource->credentialPublicKey);
        $model->user_handle = $publicKeyCredentialSource->userHandle;
        $model->counter = $publicKeyCredentialSource->counter;
        $model->user_id = $user->id;
        
        $model->save();

        return $model;
    }

    public function toPublicKeyCredentialSource(): PublicKeyCredentialSource
    {
        $trustPath = json_decode($this->trustPath, true);

        return new PublicKeyCredentialSource(
            Base64UrlSafe::decodeNoPadding($this->credential_id),
            $this->credential_type,
            json_decode($this->transports, true),
            $this->attestation_type,
            !empty($trustPath) ? new CertificateTrustPath($trustPath) : new EmptyTrustPath(),
            Uuid::fromString($this->aaguid),
            Base64UrlSafe::decodeNoPadding($this->public_key),
            $this->user_handle,
            $this->counter,
        );
    }
}
