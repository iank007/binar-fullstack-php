<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\ListUsersRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Binar Fullstack PHP API",
 *     description="REST API for the Binar Fullstack PHP Code Test",
 *     @OA\Contact(email="januar@astrnt.co")
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Sanctum API token (obtained from the seed output)"
 * )
 *
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="email", type="string", format="email", example="user@binar.co"),
 *     @OA\Property(property="name", type="string", example="Regular User"),
 *     @OA\Property(property="role", type="string", enum={"administrator","manager","user"}, example="user"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00+00:00"),
 *     @OA\Property(property="orders_count", type="integer", example=3, nullable=true),
 *     @OA\Property(property="can_edit", type="boolean", example=true, nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ApiSuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ApiFailedResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="errors", type="object")
 * )
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","name"},
     *             @OA\Property(property="email", type="string", format="email", example="newuser@binar.co"),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123"),
     *             @OA\Property(property="name", type="string", minLength=3, maxLength=50, example="New User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ApiFailedResponse")
     *     )
     * )
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return ApiResponse::success(new UserResource($user), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="List active users (paginated)",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Filter by name or email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sortBy",
     *         in="query",
     *         required=false,
     *         description="Sort column",
     *         @OA\Schema(type="string", enum={"name","email","created_at"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated user list",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=3),
     *                 @OA\Property(
     *                     property="users",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/UserResource")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        $result = $this->userService->listUsers($request->validated(), $request->user());

        return ApiResponse::success($result);
    }
}
