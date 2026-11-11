<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'loan_number',
        'client_id',
        'requested_amount',
        'application_fee',
        'approved_amount',
        'interest_rate',
        'duration_months',
        'purpose',
        'collateral_description',
        'status',
        'risk_level',
        'loan_type',
        'approved_amount',
        'monthly_payment',
        'total_amount_due',
        'outstanding_principal',
        'outstanding_interest',
        'total_paid',
        'penalty_amount',
        'days_overdue',
        'approved_by',
        'approved_at',
        'reviewed_by',
        'approval_notes',
        'verifications',
        'guarantors_count'
    ];

    public const VERIFICATION_ITEMS = [
        'cni_verified' => [
            'label' => 'Pièce d\'identité officielle de l\'emprunteur (CNI / Passeport) valide et vérifiée',
            'category' => 'Identité & Localisation',
            'icon' => 'fa-id-card',
        ],
        'home_visited' => [
            'label' => 'Visite de localisation du domicile effectuée et confirmation de résidence',
            'category' => 'Identité & Localisation',
            'icon' => 'fa-house-circle-check',
        ],
        'business_visited' => [
            'label' => 'Visite sur site de l\'activité économique / boutique / atelier réalisée',
            'category' => 'Activité & Solvabilité',
            'icon' => 'fa-shop',
        ],
        'solvency_checked' => [
            'label' => 'Capacité de remboursement & régularité des cotisations tontine/épargne validées',
            'category' => 'Activité & Solvabilité',
            'icon' => 'fa-chart-line',
        ],
        'no_dispute' => [
            'label' => 'Absence d\'impayés, d\'incidents de paiement ou de contentieux dans l\'institution',
            'category' => 'Activité & Solvabilité',
            'icon' => 'fa-shield-halved',
        ],
        'guarantors_id_verified' => [
            'label' => 'Pièces d\'identité des cautionnaires solidaires vérifiées et photocopiées',
            'category' => 'Cautions & Cautionnaires',
            'icon' => 'fa-users-viewfinder',
        ],
        'guarantors_contacted' => [
            'label' => 'Contact téléphonique et consentement formel des cautionnaires confirmés',
            'category' => 'Cautions & Cautionnaires',
            'icon' => 'fa-phone-volume',
        ],
        'guarantees_inspected' => [
            'label' => 'Garanties matérielles, gages ou engagements des cautions inventoriés',
            'category' => 'Cautions & Cautionnaires',
            'icon' => 'fa-scale-balanced',
        ],
        'contract_signed' => [
            'label' => 'Contrat de crédit et acte de cautionnement solidaire signés par toutes les parties',
            'category' => 'Conformité & Juridique',
            'icon' => 'fa-file-signature',
        ],
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'monthly_payment' => 'decimal:2',
        'total_amount_due' => 'decimal:2',
        'outstanding_principal' => 'decimal:2',
        'outstanding_interest' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'eligibility_score' => 'decimal:2',
        'guarantors_count' => 'integer',
        'verifications' => 'array',
        'application_date' => 'datetime',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'first_payment_date' => 'date',
        'maturity_date' => 'date'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function guarantors()
    {
        return $this->hasMany(LoanGuarantor::class)->orderBy('guarantor_order');
    }

    public function isVerificationItemChecked(string $key): bool
    {
        $verifs = $this->verifications ?? [];
        return !empty($verifs[$key]);
    }

    public function getVerificationsProgress(): array
    {
        $total = count(self::VERIFICATION_ITEMS);
        $checked = 0;
        $verifs = $this->verifications ?? [];
        foreach (self::VERIFICATION_ITEMS as $k => $item) {
            if (!empty($verifs[$k])) {
                $checked++;
            }
        }
        $percent = $total > 0 ? round(($checked / $total) * 100) : 0;
        return [
            'checked' => $checked,
            'total' => $total,
            'percent' => $percent,
            'percentage' => $percent,
            'is_complete' => ($checked === $total),
        ];
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disbursedBy()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    /**
     * Accessor pour le montant restant
     */
    public function getRemainingAmountAttribute()
    {
        return max(0, ($this->total_amount_due ?? 0) - ($this->total_paid ?? 0));
    }
}
