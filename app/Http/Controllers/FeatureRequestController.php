<?php

namespace App\Http\Controllers;

use App\Actions\FeatureRequests\TransitionFeatureStageAction;
use App\Enums\FeatureRequestStage;
use App\Http\Requests\FeatureRequests\UpdateFeatureStageRequest;
use App\Models\Branch;
use App\Models\FeatureRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureRequestController extends Controller
{
    /**
     * Display a paginated listing of feature requests scoped by user authorization.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeatureRequest::class);

        $user = $request->user();

        $featureRequests = FeatureRequest::with(['ticket.branch', 'ticket.creator', 'ticket.assignedEdp'])
            ->forUser($user)
            ->filter($request->all())
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $stages = FeatureRequestStage::cases();

        return view('feature_requests.index', compact('featureRequests', 'branches', 'stages'));
    }

    /**
     * Display the specified feature request details and lifecycle timeline.
     */
    public function show(FeatureRequest $featureRequest): View
    {
        $this->authorize('view', $featureRequest);

        $featureRequest->load([
            'ticket.branch',
            'ticket.creator',
            'ticket.assignedEdp',
            'ticket.attachments',
            'activities.user',
        ]);

        return view('feature_requests.show', compact('featureRequest'));
    }

    /**
     * Update the development stage and specification metadata.
     */
    public function updateStage(
        UpdateFeatureStageRequest $request,
        FeatureRequest $featureRequest,
        TransitionFeatureStageAction $action,
    ): RedirectResponse {
        $toStage = FeatureRequestStage::from($request->validated('stage'));

        $action->execute(
            featureRequest: $featureRequest,
            toStage: $toStage,
            data: $request->validated(),
            actor: $request->user(),
        );

        return redirect()->route('feature-requests.show', $featureRequest)
            ->with('success', "Feature request status transitioned to '{$toStage->label()}'.");
    }
}
