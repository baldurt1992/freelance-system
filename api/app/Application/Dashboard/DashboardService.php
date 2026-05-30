<?php

declare(strict_types=1);

namespace App\Application\Dashboard;

use App\Application\Finances\FinanceEntryService;
use App\Models\FinanceEntry;
use App\Models\Project;
use App\Models\Quote;
use Carbon\CarbonImmutable;

final class DashboardService
{
    public function __construct(
        private readonly FinanceEntryService $financeEntryService,
    ) {}

    /**
     * @return array{
     *   month: string,
     *   kpis: array{
     *     receivables_cents: int,
     *     income_cents: int,
     *     expense_cents: int,
     *     pending_quotes_count: int,
     *     active_projects_count: int
     *   },
     *   pending: array{
     *     projects_with_balance_count: int,
     *     sent_quotes_count: int,
     *     draft_quotes_count: int
     *   },
     *   financial_summary: array{
     *     income_cents: int,
     *     expense_cents: int,
     *     net_cents: int,
     *     label: string
     *   },
     *   recent_activity: list<array{
     *     id: string,
     *     kind: string,
     *     title: string,
     *     description: string,
     *     occurred_at: string,
     *     to: string
     *   }>
     * }
     */
    public function overview(string $month): array
    {
        $financialSummary = $this->financeEntryService->summaryForMonth($month);
        $monthEnd = CarbonImmutable::createFromFormat('Y-m', $month)->endOfMonth();

        $receivablesCents = $this->receivablesAtMonthEnd($monthEnd);

        $sentQuotesCount = Quote::query()
            ->whereDate('created_at', '<=', $monthEnd->toDateString())
            ->where('status', 'sent')
            ->count();
        $draftQuotesCount = Quote::query()
            ->whereDate('created_at', '<=', $monthEnd->toDateString())
            ->where('status', 'draft')
            ->count();

        $activeProjectsCount = Project::query()
            ->whereDate('created_at', '<=', $monthEnd->toDateString())
            ->where('status', 'active')
            ->count();
        $projectsWithBalanceCount = Project::query()
            ->whereDate('created_at', '<=', $monthEnd->toDateString())
            ->where('balance_due_cents', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->count();

        return [
            'month' => $month,
            'kpis' => [
                'receivables_cents' => $receivablesCents,
                'income_cents' => $financialSummary['total_income_cents'],
                'expense_cents' => $financialSummary['total_expense_cents'],
                'pending_quotes_count' => $sentQuotesCount + $draftQuotesCount,
                'active_projects_count' => $activeProjectsCount,
            ],
            'pending' => [
                'projects_with_balance_count' => $projectsWithBalanceCount,
                'sent_quotes_count' => $sentQuotesCount,
                'draft_quotes_count' => $draftQuotesCount,
            ],
            'financial_summary' => [
                'income_cents' => $financialSummary['total_income_cents'],
                'expense_cents' => $financialSummary['total_expense_cents'],
                'net_cents' => $financialSummary['net_cents'],
                'label' => $financialSummary['label'],
            ],
            'recent_activity' => $this->recentActivity($month),
        ];
    }

    private function receivablesAtMonthEnd(CarbonImmutable $monthEnd): int
    {
        $projects = Project::query()
            ->withSum([
                'payments as paid_until_month_cents' => fn ($query) => $query->whereDate('paid_at', '<=', $monthEnd->toDateString()),
            ], 'amount_cents')
            ->whereDate('created_at', '<=', $monthEnd->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get(['id', 'agreed_total_cents']);

        return (int) $projects->sum(function (Project $project): int {
            $paidUntilMonth = (int) ($project->paid_until_month_cents ?? 0);
            return max(0, (int) $project->agreed_total_cents - $paidUntilMonth);
        });
    }

    /**
     * @return list<array{
     *   id: string,
     *   kind: string,
     *   title: string,
     *   description: string,
     *   occurred_at: string,
     *   to: string
     * }>
     */
    private function recentActivity(string $month): array
    {
        [$year, $mon] = explode('-', $month);

        $financeItems = FinanceEntry::query()
            ->whereYear('occurred_on', (int) $year)
            ->whereMonth('occurred_on', (int) $mon)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (FinanceEntry $entry): array => [
                'id' => 'finance-entry-' . $entry->id,
                'kind' => 'finance_entry',
                'title' => $entry->type === 'income' ? 'Ingreso registrado' : 'Gasto registrado',
                'description' => $entry->description ?: ($entry->category ?: 'Movimiento financiero'),
                'occurred_at' => $entry->created_at->toIso8601String(),
                'to' => '/finances',
            ]);

        $quoteItems = Quote::query()
            ->whereYear('created_at', (int) $year)
            ->whereMonth('created_at', (int) $mon)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Quote $quote): array => [
                'id' => 'quote-' . $quote->id,
                'kind' => 'quote',
                'title' => 'Cotización ' . $quote->number,
                'description' => trim(($quote->title ?: 'Sin título') . ' · ' . $this->quoteStatusLabel($quote->status)),
                'occurred_at' => $quote->created_at->toIso8601String(),
                'to' => '/quotes/' . $quote->id,
            ]);

        $projectItems = Project::query()
            ->whereYear('created_at', (int) $year)
            ->whereMonth('created_at', (int) $mon)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Project $project): array => [
                'id' => 'project-' . $project->id,
                'kind' => 'project',
                'title' => $project->name,
                'description' => 'Proyecto ' . $this->projectStatusLabel($project->status) . ' · ' . $project->client_name,
                'occurred_at' => $project->created_at->toIso8601String(),
                'to' => '/projects/' . $project->id,
            ]);

        return collect($financeItems->all())
            ->merge($quoteItems->all())
            ->merge($projectItems->all())
            ->sortByDesc('occurred_at')
            ->take(8)
            ->values()
            ->all();
    }

    private function quoteStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Borrador',
            'sent' => 'Enviada',
            'accepted' => 'Aceptada',
            'rejected' => 'Rechazada',
            'converted' => 'Convertida',
            default => 'Actualizada',
        };
    }

    private function projectStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'activo',
            'on_hold' => 'en pausa',
            'completed' => 'completado',
            'cancelled' => 'cancelado',
            default => 'actualizado',
        };
    }
}
