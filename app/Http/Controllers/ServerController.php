<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServerRequest;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['sometimes', 'string', 'in:active,inactive,maintenance'],
            'provider' => ['sometimes', 'string', 'in:aws,digitalocean,vultr,other'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:id,name,ip_address,provider,status,cpu_cores,ram_mb,storage_gb,created_at,updated_at'],
            'direction' => ['sometimes', 'string', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'in:10,25,50,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = Server::query();

        $query->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
              ->when($request->filled('provider'), fn($q) => $q->where('provider', $request->provider))
              ->when($request->filled('search'), function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $request->search . '%');
              });

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 25);
        $servers = $query->paginate($perPage)->appends($request->query());

        // Return JSON response for API requests
        if ($request->wantsJson()) {
            return ServerResource::collection($servers);
        }

        $serversToArray = $servers->toArray();

        // Return Inertia response for web requests
        return Inertia::render('Servers/Index', [
            'servers' => [
                'data' => $serversToArray['data'],
            ],
            'filters' => $request->only(['status', 'provider', 'search', 'sort', 'direction', 'per_page']),
            'pagination' => [
                'current_page' => $serversToArray['current_page'],
                'last_page' => $serversToArray['last_page'],
                'per_page' => $serversToArray['per_page'],
                'total' => $serversToArray['total'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Servers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServerRequest $request)
    {
        $server = Server::create($request->validated());

        if ($request->wantsJson()) {
            return new ServerResource($server);
        }

        return redirect()->route('servers.index')
            ->with('success', 'Server created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Server $server)
    {
        if (request()->wantsJson()) {
            return new ServerResource($server);
        }

        return Inertia::render('Servers/Show', [
            'server' => (new ServerResource($server))->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Server $server)
    {
        return Inertia::render('Servers/Edit', [
            'server' => (new ServerResource($server))->resolve(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServerRequest $request, Server $server)
    {
        $server->update($request->validated());

        if ($request->wantsJson()) {
            return new ServerResource($server);
        }

        return redirect()->route('servers.index')
            ->with('success', 'Server updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Server $server)
    {
        $server->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Server deleted successfully.']);
        }

        return redirect()->route('servers.index')
            ->with('success', 'Server deleted successfully.');
    }

    /**
     * Bulk delete servers.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:servers,id'],
        ]);

        $deletedCount = Server::whereIn('id', $request->ids)->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => "{$deletedCount} servers deleted successfully."]);
        }

        return redirect()->route('servers.index')
            ->with('success', "{$deletedCount} servers deleted successfully.");
    }

    /**
     * Bulk update server status.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:servers,id'],
            'status' => ['required', 'in:active,inactive,maintenance'],
        ]);

        $updatedCount = Server::whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        if ($request->wantsJson()) {
            return response()->json(['message' => "{$updatedCount} servers updated successfully."]);
        }

        return redirect()->route('servers.index')
            ->with('success', "{$updatedCount} servers updated successfully.");
    }
}
