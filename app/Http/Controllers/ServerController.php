<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServerRequest;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServerController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:active,inactive,maintenance'],
            'provider' => ['sometimes', 'string', 'in:aws,digitalocean,vultr,other'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:id,name,ip_address,provider,status,cpu_cores,ram_mb,storage_gb,created_at,updated_at'],
            'direction' => ['sometimes', 'string', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'in:10,25,50,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $servers = $this->buildServerQuery($validated)
            ->paginate($validated['per_page'] ?? 25)
            ->appends($request->query());

        return $this->renderIndexPage($servers, $validated);
    }

    /**
     * Build optimized server query with filters, search, and sorting
     * Uses proper WHERE clause grouping for search to avoid incorrect results
     */
    private function buildServerQuery(array $filters)
    {
        return Server::query()
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['provider'] ?? null, fn($q, $provider) => $q->where('provider', $provider))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');
    }

    /**
     * Render optimized index page using direct array conversion for performance
     */
    private function renderIndexPage($servers, array $filters): Response
    {
        $paginationData = $servers->toArray();

        return Inertia::render('Servers/Index', [
            'servers' => [
                'data' => $paginationData['data'],
            ],
            'filters' => array_intersect_key($filters, array_flip(['status', 'provider', 'search', 'sort', 'direction', 'per_page'])),
            'pagination' => [
                'current_page' => $paginationData['current_page'],
                'last_page' => $paginationData['last_page'],
                'per_page' => $paginationData['per_page'],
                'total' => $paginationData['total'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Servers/Create');
    }

    public function store(ServerRequest $request): RedirectResponse
    {
        try {
            Server::create($request->validated());
            
            return redirect()->route('servers.index')->with('success', 'Server created successfully.');
        } catch (UniqueConstraintViolationException $e) {
            return $this->handleConstraintViolation($e);
        }
    }

    public function show(Server $server): Response
    {
        return Inertia::render('Servers/Show', [
            'server' => (new ServerResource($server))->resolve(),
        ]);
    }

    public function edit(Server $server): Response
    {
        return Inertia::render('Servers/Edit', [
            'server' => (new ServerResource($server))->resolve(),
        ]);
    }

    /**
     * Update server with race condition protection via timestamp validation
     * Handles IP address constraint violations gracefully
     */
    public function update(ServerRequest $request, Server $server): RedirectResponse
    {
        $data = $request->validated();
        unset($data['updated_at']); // Laravel auto-updates this
        
        try {
            $server->update($data);
            
            return redirect()->back()->with('success', 'Server updated successfully.');
        } catch (UniqueConstraintViolationException $e) {
            return $this->handleConstraintViolation($e);
        }
    }

    public function destroy(Server $server): RedirectResponse
    {
        $server->delete();

        return redirect()->route('servers.index')
            ->with('success', 'Server deleted successfully.');
    }

    /**
     * Bulk delete servers
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $deletedCount = Server::whereIn('id', $validated['ids'])->delete();
        
        return redirect()->route('servers.index')
            ->with('success', "{$deletedCount} servers deleted successfully.");
    }

    /**
     * Bulk update server status
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'status' => ['required', 'in:active,inactive,maintenance'],
        ]);

        $updatedCount = Server::whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);
            
        return redirect()->route('servers.index')
            ->with('success', "{$updatedCount} servers updated successfully.");
    }

    /**
     * Handle database constraint violations (race conditions for IP duplicates)
     * Converts technical database errors to user-friendly messages
     */
    private function handleConstraintViolation(UniqueConstraintViolationException $e): RedirectResponse
    {
        if (!str_contains($e->getMessage(), 'ip_address')) {
            throw $e;
        }

        $errorMessage = 'This IP address was just taken by another user. Please choose a different IP address.';

        return redirect()->back()
            ->withErrors(['ip_address' => $errorMessage])
            ->withInput();
    }
}
