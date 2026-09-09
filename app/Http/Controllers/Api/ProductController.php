<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request)
    {
        $search = $request->query('search');
        $filters = $request->safe()->except(['search', 'sort']);
        $sort = $request->input('sort');

        $perPage = $request->integer('per_page', 10);

        $cacheKey = 'products:listing:v'.Product::listingCacheVersion().':'.sha1(json_encode([
            'page' => $request->integer('page', 1),
            'per_page' => $perPage,
            'search' => $search,
            'filters' => $filters,
            'sort' => $sort,
        ], JSON_THROW_ON_ERROR));

        $payload = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($search, $filters, $sort, $perPage) {
            $paginator = Product::query()
                ->published()
                ->with([
                    'store:id,name',
                    'category' => fn ($q) => $q->withTrashed()->select('id', 'name', 'slug'),
                    'tags' => fn ($q) => $q->withTrashed()->select('tags.id', 'tags.name', 'tags.slug'),
                    'images',
                ])
                ->search($search ?? null)
                ->filter($filters)
                ->sort($sort)
                ->paginate($perPage);

            return [
                'items' => collect($paginator->items())->map(fn (Product $product) => [
                    'attributes' => $product->getAttributes(),
                    'store' => $product->store?->getAttributes(),
                    'category' => $product->category?->getAttributes(),
                    'tags' => $product->tags->map(fn (Tag $tag) => $tag->getAttributes())->all(),
                    'images' => $product->images->map(fn (ProductImage $image) => $image->getAttributes())->all(),
                ])->all(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'path' => $paginator->path(),
                'query' => $paginator->getOptions()['query'] ?? [],
            ];
        });

        $products = new LengthAwarePaginator(
            items: collect($payload['items'])->map(function (array $item) {
                $product = (new Product)->newFromBuilder($item['attributes']);
                $product->setRelation('store', $item['store'] === null ? null : (new Store)->newFromBuilder($item['store']));
                $product->setRelation('category', $item['category'] === null ? null : (new Category)->newFromBuilder($item['category']));
                $product->setRelation(
                    'tags',
                    new EloquentCollection(
                        collect($item['tags'])->map(fn (array $tag) => (new Tag)->newFromBuilder($tag))->all()
                    )
                );
                $product->setRelation(
                    'images',
                    new EloquentCollection(
                        collect($item['images'] ?? [])->map(fn (array $image) => (new ProductImage)->newFromBuilder($image))->all()
                    )
                );

                return $product;
            }),
            total: $payload['total'],
            perPage: $payload['per_page'],
            currentPage: $payload['current_page'],
            options: [
                'path' => $payload['path'],
                'query' => $payload['query'],
            ],
        );

        return $this->pagination(
            paginator: $products,
            data: ProductResource::collection($products),
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $public_product)
    {
        $product = $public_product->load([
            'store',
            'category' => fn ($q) => $q->withTrashed()->select('id', 'name', 'slug'),
            'tags' => fn ($q) => $q->withTrashed(),
            'images',
        ]);

        return $this->success(new ProductResource($product));
    }
}
