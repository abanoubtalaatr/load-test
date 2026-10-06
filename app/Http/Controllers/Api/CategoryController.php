<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: "/api/categories",
        summary: "List categories",
        tags: ["Categories"],
        responses: [
            new OA\Response(response: 200, description: "Paginated list of categories"),
        ]
    )]
    public function index()
    {
        return CategoryResource::collection(Category::latest()->paginate(15));
    }

    #[OA\Post(
        path: "/api/categories",
        summary: "Create a category",
        tags: ["Categories"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["name"],
                    properties: [
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Category created"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storeImage($request->file('image'));
        }

        $category = Category::create($validated);

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: "/api/categories/{category}",
        summary: "Get a category",
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(name: "category", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Category details"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function show(Category $category)
    {
        return CategoryResource::make($category);
    }

    #[OA\Put(
        path: "/api/categories/{category}",
        summary: "Update a category",
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(name: "category", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Category updated"),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($category);
            $validated['image'] = $this->storeImage($request->file('image'));
        }

        $category->update($validated);

        return CategoryResource::make($category);
    }

    #[OA\Delete(
        path: "/api/categories/{category}",
        summary: "Delete a category",
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(name: "category", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 204, description: "Category deleted"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function destroy(Category $category)
    {
        $this->deleteImage($category);
        $category->delete();

        return response()->json(null, 204);
    }

    private function storeImage(UploadedFile $image): string
    {
        return $image->store('categories', 'public');
    }

    private function deleteImage(Category $category): void
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
    }
}
