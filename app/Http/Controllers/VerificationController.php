<?php

namespace App\Http\Controllers;

use App\Enums\ReviewDecision;
use App\Enums\ReviewStatus;
use App\Models\EvidenceReview;
use App\Models\User;
use App\Services\OrganizationScope;
use App\Services\VerificationService;
use App\Support\Present;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verification,
        private readonly OrganizationScope $scope,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('verify');
        $user = $request->user();
        $tab = $request->string('tab')->value() === 'history' ? 'history' : 'queue';

        $query = $tab === 'queue'
            ? $this->verification->queueFor($user)->orderBy('created_at')
            : EvidenceReview::query()
                ->whereNotNull('decided_at')
                ->whereHas('evidence', fn (Builder $query) => $this->scope->apply($query, $user))
                ->when(! $request->boolean('all'), fn ($query) => $query->where('decided_by', $user->id))
                ->latest('decided_at');

        $reviews = $query
            ->with(['evidence:id,title,category,organization_id,period_id,uploaded_by', 'evidence.organization:id,name', 'evidence.period:id,name', 'evidence.uploader:id,name', 'evidenceVersion:id,version_number', 'decider:id,name', 'assignedVerifier:id,name'])
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->whereHas('evidence', fn ($query) => $query->where('title', 'like', "%{$search}%")))
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('status', $status))
            ->paginate(20)
            ->withQueryString()
            ->through(fn (EvidenceReview $review) => [
                'id' => $review->id,
                'evidence' => ['id' => $review->evidence->id, 'title' => $review->evidence->title, 'category' => $review->evidence->category->label()],
                'organization' => $review->evidence->organization->name,
                'period' => $review->evidence->period->name,
                'uploader' => Present::user($review->evidence->uploader),
                'version_number' => $review->evidenceVersion->version_number,
                'stage_name' => $review->stage_name,
                'status' => Present::enum($review->status),
                'assigned_verifier' => Present::user($review->assignedVerifier),
                'decider' => Present::user($review->decider),
                'decided_at' => Present::datetime($review->decided_at),
                'decision_note' => $review->decision_note,
                'created_at' => Present::datetime($review->created_at),
            ]);

        return Inertia::render('verifications/index', [
            'reviews' => $reviews,
            'tab' => $tab,
            'filters' => $request->only(['search', 'status', 'all']),
            'statusOptions' => collect(ReviewStatus::options())->values(),
            'queueCount' => $this->verification->queueFor($user)->count(),
        ]);
    }

    public function start(Request $request, EvidenceReview $review): RedirectResponse
    {
        abort_unless($this->verification->canAct($request->user(), $review), 403);

        $this->verification->start($request->user(), $review);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Verifikasi dimulai.']);

        return to_route('evidence.show', $review->evidence_id);
    }

    public function decide(Request $request, EvidenceReview $review): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::enum(ReviewDecision::class)],
            // A reason is mandatory whenever the evidence is not accepted.
            'note' => ['nullable', 'required_unless:decision,'.ReviewDecision::Accept->value, 'string', 'max:5000'],
        ], ['note.required_unless' => 'Catatan wajib diisi untuk permintaan revisi atau penolakan.'], ['decision' => 'keputusan', 'note' => 'catatan']);

        $decision = ReviewDecision::from($data['decision']);
        abort_unless($this->verification->canDecide($request->user(), $review, $decision), 403);

        $this->verification->decide($request->user(), $review, $decision, $data['note'] ?? null);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Keputusan \"{$decision->label()}\" tersimpan."]);

        return to_route('evidence.show', $review->evidence_id);
    }

    public function assign(Request $request, EvidenceReview $review): RedirectResponse
    {
        Gate::authorize('assign', $review->evidence);

        $data = $request->validate(['assignee_id' => ['required', 'integer', Rule::exists(User::class, 'id')]], attributes: ['assignee_id' => 'verifikator']);

        $this->verification->assignVerifier($review, User::query()->findOrFail((int) $data['assignee_id']));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Verifikator ditetapkan untuk tahap ini.']);

        return to_route('evidence.show', $review->evidence_id);
    }
}
