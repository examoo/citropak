<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/**
 * DISTRIBUTION MODEL
 * 
 * Root tenant model for multi-tenant architecture.
 * Each distribution (DMS, TMS, LMS) is an isolated business unit.
 * 
 * NOTE: This model does NOT use BaseTenantModel trait
 * as it IS the tenant itself.
 * 
 * FBR INTEGRATION:
 * Each distribution can have its own NTN/STRN and FBR API credentials.
 */
class Distribution extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
        // New fields
        'address',
        'phone_number',
        // FBR Business Details
        'ntn_number',
        'strn_number',
        'stn_number',
        'business_name',
        'business_address',
        'business_phone',
        'business_email',
        // Tax details
        'sales_tax_status',
        'filer_status',
        // FBR API Settings
        'pos_id',
        'fbr_username',
        'fbr_password',
        'fbr_environment',
        'fbr_enabled',
        'fbr_integration_date',
    ];

    protected $casts = [
        'fbr_enabled' => 'boolean',
        'fbr_integration_date' => 'date',
    ];

    /**
     * Hidden attributes (for API responses).
     */
    protected $hidden = [
        'fbr_username',
        'fbr_password',
    ];

    /**
     * Get all users belonging to this distribution.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all customers belonging to this distribution.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Scope to active distributions only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to FBR-enabled distributions only.
     */
    public function scopeFbrEnabled($query)
    {
        return $query->where('fbr_enabled', true);
    }

    /**
     * Check if FBR integration is enabled.
     */
    public function isFbrEnabled(): bool
    {
        return $this->fbr_enabled && 
               !empty($this->pos_id) && 
               !empty($this->fbr_username) && 
               !empty($this->fbr_password);
    }

    /**
     * Get the FBR API base URL based on environment.
     */
    public function getFbrApiUrl(): string
    {
        return $this->fbr_environment === 'production'
            ? config('fbr.production_url', 'https://ims.fbr.gov.pk/ims-api')
            : config('fbr.sandbox_url', 'https://sandbox.fbr.gov.pk/ims-api');
    }

    /**
     * Set FBR username (encrypted).
     */
    public function setFbrUsernameAttribute($value)
    {
        $this->attributes['fbr_username'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get FBR username (decrypted).
     */
    public function getFbrUsernameDecryptedAttribute(): ?string
    {
        try {
            return $this->fbr_username ? Crypt::decryptString($this->fbr_username) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set FBR password (encrypted).
     */
    public function setFbrPasswordAttribute($value)
    {
        $this->attributes['fbr_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get FBR password (decrypted).
     */
    public function getFbrPasswordDecryptedAttribute(): ?string
    {
        try {
            return $this->fbr_password ? Crypt::decryptString($this->fbr_password) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get formatted NTN for display.
     */
    public function getFormattedNtnAttribute(): ?string
    {
        return $this->ntn_number;
    }

    /**
     * Get formatted STRN for display.
     */
    public function getFormattedStrnAttribute(): ?string
    {
        return $this->strn_number;
    }
}
