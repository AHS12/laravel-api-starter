<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RoleInsertUpdateRequest;
use App\Http\Requests\Auth\RolePermissionAssignRequest;
use App\Http\Resources\Auth\RoleResource;
use App\Http\Services\Auth\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    private $rolePermissionService;
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::with('permissions')->get();
        return RoleResource::collection($roles);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleInsertUpdateRequest $request)
    {
        $this->authorize('create', Role::class);
        $role = $this->rolePermissionService->insertRole(
            $request->name,
            $request->permissions ?? []
        );
        return RoleResource::make($role);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $role = Role::findOrFail($id);
        $this->authorize('view', $role);
        return RoleResource::make($role);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleInsertUpdateRequest $request)
    {
        $role = Role::findOrFail($request->id);
        $this->authorize('update', $role);
        $name = $request->name;
        $updatedRole = $this->rolePermissionService->updateRole($role, $name);
        return RoleResource::make($updatedRole);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->authorize('delete', $role);
        $this->rolePermissionService->deleteRole($role);
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function assignPermission(RolePermissionAssignRequest $request)
    {
        $this->authorize('create', Role::class);
        $role = Role::findOrFail($request->id);
        $role->syncPermissions($request->permissions);
        return RoleResource::make($role->load('permissions'));
    }
}