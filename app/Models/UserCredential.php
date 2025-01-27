<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

        $model->credential_id = $publicKeyCredentialSource->publicKeyCredentialId;
        $model->credential_type = $publicKeyCredentialSource->type;
        $model->transports = $publicKeyCredentialSource->transports;
        $model->attestation_type = $publicKeyCredentialSource->attestationType;
        $model->trust_path = $publicKeyCredentialSource->trustPath instanceof EmptyTrustPath ? [] : $publicKeyCredentialSource->trustPath->certificates;
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
            $this->credential_id,
            $this->credential_type,
            $this->transports,
            $this->attestation_type,
            !empty($trustPath) ? new CertificateTrustPath($trustPath) : new EmptyTrustPath(),
            Uuid::fromString($this->aaguid),
            Base64UrlSafe::decodeNoPadding($this->public_key),
            $this->user_handle,
            $this->counter,
        );
    }

    protected function casts(): array
    {
        return [
            "transports" => "array",
            "trust_path" => "array",
        ];
    }

    protected function credentialId(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Base64UrlSafe::decodeNoPadding($value),
            set: fn(string $value) => Base64UrlSafe::encodeUnpadded($value),
        );
    }

    protected function publicKey(): Attrbute
    {
        return Attribute::make(
            get: fn (string $value) => Base64UrlSafe::decodeNoPadding($value),
            set: fn(string $value) => Base64UrlSafe::encodeUnpadded($value),
        );
    }
}
