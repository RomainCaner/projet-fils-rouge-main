<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Paginator;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Validator;
use Cyna\Repositories\CategoryRepository;
use Cyna\Repositories\ProductRepository;
use Cyna\Support\Upload;

/**
 * Gestion des services SaaS au back-office : liste triable et paginée,
 * création, modification, et suppression multiple.
 */
final class ProductController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): Response
    {
        $page = $request->int('page', 1);
        $sort = $request->string('sort', 'name');
        $dir = $request->string('dir', 'asc');
        $search = $request->string('q');

        $result = ProductRepository::paginateForAdmin(($page - 1) * self::PER_PAGE, self::PER_PAGE, $sort, $dir, $search);

        return $this->view('admin/products/index', [
            'title'     => 'Produits',
            'products'  => $result['items'],
            'paginator' => new Paginator($result['total'], $page, self::PER_PAGE),
            'sort'      => $sort,
            'dir'       => $dir,
            'q'         => $search,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin/products/form', [
            'title'      => 'Nouveau produit',
            'product'    => null,
            'categories' => CategoryRepository::all(),
        ]);
    }

    public function store(Request $request): Response
    {
        if ($errors = $this->validateProduct($request)) {
            return $this->back($errors, $request->all());
        }

        $data = $this->extractData($request);
        $data['image'] = $this->uploadImage($request);
        ProductRepository::create($data);

        Session::flash('success', 'Produit créé.');

        return $this->redirect('/admin/produits');
    }

    public function edit(Request $request, string $id): Response
    {
        $product = ProductRepository::find((int) $id);
        if ($product === null) {
            throw new HttpException(404, 'Produit introuvable.');
        }

        return $this->view('admin/products/form', [
            'title'      => 'Modifier : ' . $product['name'],
            'product'    => $product,
            'categories' => CategoryRepository::all(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        if (ProductRepository::find((int) $id) === null) {
            throw new HttpException(404, 'Produit introuvable.');
        }
        if ($errors = $this->validateProduct($request)) {
            return $this->back($errors, $request->all());
        }

        $data = $this->extractData($request);
        $data['image'] = $this->uploadImage($request); // null = conserve l'image actuelle
        ProductRepository::update((int) $id, $data);

        Session::flash('success', 'Produit mis à jour.');

        return $this->redirect('/admin/produits');
    }

    public function bulkDelete(Request $request): Response
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        ProductRepository::deleteMany(array_values(array_filter($ids)));

        Session::flash('success', 'Produits supprimés.');

        return $this->redirect('/admin/produits');
    }

    /** @return array<string,list<string>> */
    private function validateProduct(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'category_id'       => 'required|numeric',
            'name'              => 'required|max:160',
            'short_description' => 'required|max:255',
            'description'       => 'required',
            'price_monthly'     => 'required|numeric',
            'price_annual'      => 'required|numeric',
            'availability'      => 'required|in:available,maintenance',
        ]);

        return $validator->fails() ? $validator->errors() : [];
    }

    /** @return array<string,mixed> */
    private function extractData(Request $request): array
    {
        $name = $request->string('name');
        $slug = $request->string('slug') !== '' ? slugify($request->string('slug')) : slugify($name);

        return [
            'category_id'         => $request->int('category_id'),
            'slug'                => $slug,
            'name'                => $name,
            'short_description'   => $request->string('short_description'),
            'description'         => $request->string('description'),
            'tech_specs'          => $request->string('tech_specs') ?: null,
            'price_monthly_cents' => (int) round((float) $request->string('price_monthly') * 100),
            'price_annual_cents'  => (int) round((float) $request->string('price_annual') * 100),
            'availability'        => $request->string('availability'),
            'priority'            => $request->int('priority'),
            'is_featured'         => $request->boolean('is_featured') ? 1 : 0,
            'featured_position'   => $request->int('featured_position'),
        ];
    }

    private function uploadImage(Request $request): ?string
    {
        $file = $request->file('image');

        return $file !== null ? Upload::store($file, 'products') : null;
    }
}
