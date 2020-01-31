<?php

namespace App\Models;

use Advoor\NovaEditorJs\NovaEditorJs;
use App\Models\States\Enrollment\Cancelled as CancelledState;
use App\Models\Traits\HasEditorJsContent;
use App\Traits\HasPaperclip;
use Czim\Paperclip\Config\Steps\ResizeStep;
use Czim\Paperclip\Config\Variant;
use Czim\Paperclip\Contracts\AttachableInterface;
use Czim\Paperclip\Model\PaperclipTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Whitecube\NovaFlexibleContent\Concerns\HasFlexible;

/**
 * A hosted activity
 *
 * @author Roelof Roos <github@roelof.io>
 * @license MPL-2.0
 *
 * @property-read AttachmentInterface $image
 */
class Activity extends SluggableModel implements AttachableInterface
{
    use PaperclipTrait;
    use HasPaperclip;
    use HasEditorJsContent;
    use HasFlexible;

    public const PAYMENT_TYPE_INTENT = 'intent';
    public const PAYMENT_TYPE_BILLING = 'billing';

    /**
     * @inheritDoc
     */
    protected $dates = [
        // Management dates
        'created_at',
        'updated_at',
        'deleted_at',
        'cancelled_at',

        // Start date
        'start_date',
        'end_date',

        // Enrollment date
        'enrollment_start',
        'enrollment_end'
    ];

    /**
     * @inheritDoc
     */
    protected $casts = [
        // Description
        'description' => 'json',

        // Number of seats
        'seats' => 'int',
        'is_public' => 'bool',

        // Pricing
        'member_discount' => 'int',
        'discount_count' => 'int',
        'price' => 'int',

        // Extra information
        'enrollment_questions' => 'collection',
    ];

    /**
     * Binds paperclip files
     *
     * @return void
     */
    protected function bindPaperclip(): void
    {
        // Max sizes
        $bannerWidth = max(
            640, // Landscape phones
            768 / 12 * 6, // tables
            1024 / 12 * 4, // small laptops
            1280 / 12 * 4, // hd laptops
        );

        // Banner width:height is 2:1
        $bannerHeight = $bannerWidth / 2;

        $coverWidth = 1920; // Full HD width
        $coverHeight = 33 * 16; // 33rem

        // The actual screenshots
        $this->hasAttachedFile('image', [
            'disk' => 'paperclip-public',
            'variants' => [
                // Make banner-sized image (HD and HDPI)
                Variant::make('banner')->steps([
                    ResizeStep::make()->width($bannerWidth)->height($bannerHeight)->crop()
                ])->extension('jpg'),
                Variant::make('banner@2x')->steps([
                    ResizeStep::make()->width($bannerWidth * 2)->height($bannerHeight * 2)->crop()
                ])->extension('jpg'),

                // Make activity cover image (HD and HDPI)
                Variant::make('cover')->steps([
                    ResizeStep::make()->width($coverWidth)->height($coverHeight)->crop()
                ])->extension('jpg'),
                Variant::make('cover@2x')->steps([
                    ResizeStep::make()->width($coverWidth * 2)->height($coverHeight * 2)->crop()
                ])->extension('jpg'),

                // Make Social Media
                Variant::make('social')->steps([
                    ResizeStep::make()->width(1200)->height(650)->crop()
                ])->extension('jpg'),
            ]
        ]);
    }

    /**
     * Generate the slug based on the title property
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'unique' => true,
            ]
        ];
    }

    /**
     * Returns the associated role, if any
     *
     * @return BelongsTo
     */
    public function role(): Relation
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * Returns all enrollments (both pending and active)
     *
     * @return HasMany
     */
    public function enrollments(): Relation
    {
        return $this->hasMany(Enrollment::class)
            ->whereNotState('state', CancelledState::class);
    }

    /**
     * Returns all made payments for this event
     *
     * @return HasMany
     */
    public function payments(): Relation
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Returns if the activity has been cancelled
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getCancelledAttribute(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Returns the name of the organiser, either committee or user
     *
     * @return string|null
     */
    public function getOrganiserAttribute(): ?string
    {
        return optional($this->role)->title;
    }

    /**
     * Returns the number of remaining seats
     *
     * @return int
     */
    public function getAvailableSeatsAttribute(): int
    {
        // Only if there are actually places
        if ($this->seats === null) {
            return PHP_INT_MAX;
        }

        // Get enrollment count
        $occupied = $this->enrollments()
            ->whereNotState('state', CancelledState::class)
            ->count();

        // Subtract active enrollments from active seats
        return (int) max(0, $this->seats - $occupied);
    }

    /**
     * Returns if the enrollment is still open
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getEnrollmentOpenAttribute(): ?bool
    {
        // Don't re-create a timestamp every time
        $now = now();

        // Cannot sell tickets after activity end
        if ($this->end_date < $now) {
            logger()->info(
                'Enrollments on {activity} closed:  Cannot sell tickets after activity end',
                ['activity' => $this]
            );
            return false;
        }

        // Cannot sell tickets after enrollment closure
        if ($this->enrollment_end !== null && $this->enrollment_end < $now) {
            logger()->info(
                'Enrollments on {activity} closed:  Cannot sell tickets after enrollment closure',
                ['activity' => $this]
            );
            return false;
        }

        // Cannot sell tickets before enrollment start
        if ($this->enrollment_start !== null && $this->enrollment_start > $now) {
            logger()->info(
                'Enrollments on {activity} closed:  Cannot sell tickets before enrollment start',
                ['activity' => $this]
            );
            return false;
        }

        // Enrollment start < now < (Enrollment end | Event end)
        return true;
    }

    /**
     * Converts contents to HTML
     *
     * @return string|null
     */
    public function getDescriptionHtmlAttribute(): ?string
    {
        return $this->convertToHtml($this->description);
    }

    /**
     * Enrollment form
     *
     * @return Whitecube\NovaFlexibleContent\Layouts\Collection
     */
    public function getFlexibleContentAttribute()
    {
        return $this->flexible('enrollment_questions');
    }

    /**
     * Returns the price for people with discounts
     * @return null|int
     */
    public function getDiscountPriceAttribute(): ?int
    {
        // Return null if no discounts are available
        if (!$this->member_discount) {
            return null;
        }

        // Member price
        return max(0, $this->price - $this->member_discount);
    }

    /**
     * Returns member price with transfer costs
     *
     * @return int|null
     */
    public function getTotalDiscountPriceAttribute(): ?int
    {
        // Return null if no discounts are available
        if (!$this->member_discount) {
            return null;
        }

        // Member price
        $memberPrice = $this->price - $this->member_discount;

        // Return discount
        return $memberPrice ? ($memberPrice + config('gumbo.transfer-fee', 0)) : 0;
    }

    /**
     * Returns the number of discounts available, if any
     * @return null|int
     */
    public function getDiscountsAvailableAttribute(): ?int
    {
        // None if no discount is available
        if (!$this->member_discount) {
            return null;
        }

        // Infinite if zero or empty
        if (!$this->discount_count) {
            return \INF;
        }

        // Count them
        return max(0, $this->discount_count - $this->enrollments()->where('user_type', 'member')->count());
    }

    /**
     * Returns guest price with transfer cost
     *
     * @return int|null
     */
    public function getTotalPriceAttribute(): ?int
    {
        return $this->price ? $this->price + config('gumbo.transfer-fee', 0) : $this->price;
    }

    /**
     * Returns human-readable summary of the ticket price.
     *
     * @return string
     */
    public function getPriceLabelAttribute(): string
    {
        if ($this->is_free) {
            // If it's free, mention it
            return 'gratis';
        } elseif ($this->price && $this->member_discount === $this->price && $this->discount_count) {
            // Free for members
            return sprintf('gratis voor %d leden', $this->discount_count);
        } elseif ($this->price && $this->member_discount === $this->price) {
            // Free for members
            return 'gratis voor leden (beperkte korting)';
        } elseif ($this->member_discount && $this->price && $this->discount_count) {
            // Discounted for members
            return sprintf('vanaf %s (beperkte korting)', Str::price($this->total_discount_price ?? 0));
        } elseif ($this->member_discount && $this->price) {
            // Discounted for members
            return sprintf('vanaf %s', Str::price($this->total_discount_price ?? 0));
        }

        // Return total price as single price point
        return Str::price($this->total_price ?? 0);
    }

    /**
     * Returns if members can go for free
     * @return bool
     */
    public function getIsFreeForMemberAttribute(): bool
    {
        return $this->is_free ||
            ($this->member_discount === $this->price && $this->discount_count === null);
    }

    /**
     * Returns true if the activity is free
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsFreeAttribute(): bool
    {
        return $this->total_price === null;
    }

    /**
     * Only return activities available to this user
     *
     * @param Builder $query
     * @param User $user
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable(Builder $query, User $user = null): Builder
    {
        /** @var User $user */
        $user = $user ?? request()->user();

        // Add public-only when not a member
        return $user && $user->is_member ? $query : $query->whereIsPublic(true);
    }

    /**
     * Returns url to map provider for the given address
     *
     * @return null|string
     */
    public function getLocationUrlAttribute(): ?string
    {
        // Skip if empty
        if (empty($this->location_address)) {
            return null;
        }

        // Build HERE maps link
        return sprintf(
            'https://www.qwant.com/maps/?%s',
            \http_build_query(['q' => $this->location_address])
        );
    }
}
