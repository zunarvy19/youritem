<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Enums\WishlistStatus;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;

class ShoppingRecommendationService
{
    /**
     * Hard cap on candidates fed to the optimization search to keep the
     * combinatorial brute force bounded (2^20 worst case is too much,
     * so we cap at 18 => 262k subsets).
     */
    private const OPTIMIZATION_CANDIDATE_LIMIT = 18;

    private const PRIORITY_SCORES = [
        Priority::High->value => 3,
        Priority::Medium->value => 2,
        Priority::Low->value => 1,
    ];

    private const PURPOSE_SCORES = [
        Purpose::Need->value => 2,
        Purpose::Want->value => 1,
    ];

    public function __construct(
        private readonly BudgetService $budgetService,
    ) {}

    /**
     * Full recommendation result per docs/DATABASE.md §34.
     *
     * @return array<string, mixed>
     */
    public function recommend(User $user): array
    {
        $availableBudget = $this->budgetService->getAvailableBudget($user);
        $candidates = $this->activeCandidates($user);

        $priorityFirst = $this->priorityFirst($candidates, $availableBudget);
        $optimization = $this->budgetOptimization($candidates, $availableBudget);

        $unaffordable = $candidates
            ->filter(fn (WishlistItem $item): bool => $item->estimated_price > $availableBudget)
            ->values()
            ->map(fn (WishlistItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => ['id' => $item->category->id, 'name' => $item->category->name],
                'priority' => $item->priority->value,
                'purpose' => $item->purpose->value,
                'estimated_price' => $item->estimated_price,
                'amount_needed' => $item->estimated_price - $availableBudget,
            ])
            ->all();

        return [
            'available_budget' => $availableBudget,
            'priority_first' => $priorityFirst,
            'budget_optimization' => $optimization,
            'unaffordable' => $unaffordable,
        ];
    }

    /**
     * Priority First: deterministic ranking (purpose, priority, price, created, id)
     * followed by sequential budget allocation. Items that do not fit are skipped
     * without consuming budget and land in "unaffordable".
     *
     * @param  Collection<int, WishlistItem>  $candidates
     * @return array<string, mixed>
     */
    private function priorityFirst($candidates, int $availableBudget): array
    {
        $ranked = $candidates->sortBy([
            fn (WishlistItem $a, WishlistItem $b): int => $this->compareDesc($this->purposeRank($a), $this->purposeRank($b)),
            fn (WishlistItem $a, WishlistItem $b): int => $this->compareDesc($this->priorityRank($a), $this->priorityRank($b)),
            fn (WishlistItem $a, WishlistItem $b): int => $a->estimated_price <=> $b->estimated_price,
            fn (WishlistItem $a, WishlistItem $b): int => (string) $a->created_at <=> (string) $b->created_at,
            fn (WishlistItem $a, WishlistItem $b): int => $a->id <=> $b->id,
        ])->values();

        $selected = [];
        $remaining = $availableBudget;
        $total = 0;

        foreach ($ranked as $item) {
            if ($item->estimated_price <= $remaining) {
                $selected[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => ['id' => $item->category->id, 'name' => $item->category->name],
                    'priority' => $item->priority->value,
                    'purpose' => $item->purpose->value,
                    'estimated_price' => $item->estimated_price,
                    'reasons' => $this->reasonsFor($item),
                ];
                $remaining -= $item->estimated_price;
                $total += $item->estimated_price;
            }
        }

        return [
            'items' => $selected,
            'total' => $total,
            'remaining_budget' => $remaining,
        ];
    }

    /**
     * Budget Optimization: bounded knapsack maximizing total score
     * (priority x purpose), then preferring higher utilization,
     * then fewer items, then lexicographically lowest ids for determinism.
     *
     * @param  Collection<int, WishlistItem>  $candidates
     * @return array<string, mixed>
     */
    private function budgetOptimization($candidates, int $availableBudget): array
    {
        $pool = $candidates
            ->sortBy([
                fn (WishlistItem $a, WishlistItem $b): int => $this->compareDesc($this->scoreOf($a), $this->scoreOf($b)),
                fn (WishlistItem $a, WishlistItem $b): int => $a->estimated_price <=> $b->estimated_price,
                fn (WishlistItem $a, WishlistItem $b): int => $a->id <=> $b->id,
            ])
            ->take(self::OPTIMIZATION_CANDIDATE_LIMIT)
            ->values();

        if ($pool->isEmpty() || $availableBudget <= 0) {
            return $this->emptyOptimization($availableBudget);
        }

        $items = $pool->all();
        $count = count($items);

        /** @var array{score: int, total: int, mask: int}|null $best */
        $best = null;

        $totalCombinations = 1 << $count;
        for ($mask = 1; $mask < $totalCombinations; $mask++) {
            $priceSum = 0;
            $scoreSum = 0;

            for ($i = 0; $i < $count; $i++) {
                if (($mask >> $i) & 1) {
                    $priceSum += $items[$i]->estimated_price;
                    $scoreSum += $this->scoreOf($items[$i]);
                }
            }

            if ($priceSum > $availableBudget) {
                continue;
            }

            $candidate = ['score' => $scoreSum, 'total' => $priceSum, 'mask' => $mask];

            if ($best === null || $this->isBetterCombination($candidate, $best)) {
                $best = $candidate;
            }
        }

        if ($best === null) {
            return $this->emptyOptimization($availableBudget);
        }

        $selected = [];
        for ($i = 0; $i < $count; $i++) {
            if (($best['mask'] >> $i) & 1) {
                $selected[] = $items[$i];
            }
        }

        usort($selected, fn (WishlistItem $a, WishlistItem $b): int => $a->id <=> $b->id);

        return [
            'items' => array_map(
                fn (WishlistItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => ['id' => $item->category->id, 'name' => $item->category->name],
                    'priority' => $item->priority->value,
                    'purpose' => $item->purpose->value,
                    'estimated_price' => $item->estimated_price,
                ],
                $selected,
            ),
            'total' => $best['total'],
            'remaining_budget' => $availableBudget - $best['total'],
            'score' => $best['score'],
            'utilization' => $this->utilization($best['total'], $availableBudget),
        ];
    }

    /**
     * Objective order per SHOPPING-ALGORITHM.md §17:
     * score first, then utilization, then fewer items, then lower id mask.
     *
     * @param  array{score: int, total: int, mask: int}  $candidate
     * @param  array{score: int, total: int, mask: int}  $currentBest
     */
    private function isBetterCombination(array $candidate, array $currentBest): bool
    {
        if ($candidate['score'] !== $currentBest['score']) {
            return $candidate['score'] > $currentBest['score'];
        }

        if ($candidate['total'] !== $currentBest['total']) {
            return $candidate['total'] > $currentBest['total'];
        }

        return $candidate['mask'] < $currentBest['mask'];
    }

    /**
     * Active items with valid prices only (SHOPPING-ALGORITHM.md §5).
     *
     * @return Collection<int, WishlistItem>
     */
    private function activeCandidates(User $user): Collection
    {
        return $user->wishlistItems()
            ->with('category')
            ->where('status', WishlistStatus::Active)
            ->where('estimated_price', '>', 0)
            ->orderBy('id')
            ->get();
    }

    /** @return array<string, mixed> */
    private function emptyOptimization(int $availableBudget): array
    {
        return [
            'items' => [],
            'total' => 0,
            'remaining_budget' => max(0, $availableBudget),
            'score' => 0,
            'utilization' => 0.0,
        ];
    }

    /** @return list<string> */
    private function reasonsFor(WishlistItem $item): array
    {
        $reasons = [];

        $reasons[] = $item->purpose === Purpose::Need ? 'Need' : 'Want';
        $reasons[] = match ($item->priority) {
            Priority::High => 'High priority',
            Priority::Medium => 'Medium priority',
            Priority::Low => 'Low priority',
        };

        return $reasons;
    }

    private function scoreOf(WishlistItem $item): int
    {
        return self::PRIORITY_SCORES[$item->priority->value] * self::PURPOSE_SCORES[$item->purpose->value];
    }

    private function purposeRank(WishlistItem $item): int
    {
        return self::PURPOSE_SCORES[$item->purpose->value];
    }

    private function priorityRank(WishlistItem $item): int
    {
        return self::PRIORITY_SCORES[$item->priority->value];
    }

    private function utilization(int $total, int $availableBudget): float
    {
        if ($availableBudget <= 0) {
            return 0.0;
        }

        return round($total / $availableBudget, 4);
    }

    private function compareDesc(int $a, int $b): int
    {
        return $b <=> $a;
    }
}
