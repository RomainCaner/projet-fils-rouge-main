<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Validator;
use Cyna\Repositories\CategoryRepository;
use Cyna\Support\Upload;

/**
 * Gestion des catégories de services (création, édition, ordre d'affichage,
 * suppression).
 */
final class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin/categories/index', [
            'title'      => 'Catégories',
            'categories' => CategoryRepository::allWithCounts(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin/categories/form', ['title' => 'Nouvelle catégorie', 'category' => null]);
    }

    public function store(Request $request): Response
    {
        if ($errors = $this->validate($request)) {
            return $this->back($errors, $request->all());
        }

        CategoryRepository::create(
            $request->string('name'),
            $this->slug($request),
            $request->string('description'),
            $this->uploadImage($request),
            $request->int('position'),
        );
        Session::flash('success', 'Catégorie créée.');

        return $this->redirect('/admin/categories');
    }

    public function edit(Request $request, string $id): Response
    {
        $category = CategoryRepository::find((int) $id);
        if ($category === null) {
            throw new HttpException(404, 'Catégorie introuvable.');
        }

        return $this->view('admin/categories/form', ['title' => 'Modifier la catégorie', 'category' => $category]);
    }

    public function update(Request $request, string $id): Response
    {
        if (CategoryRepository::find((int) $id) === null) {
            throw new HttpException(404, 'Catégorie introuvable.');
        }
        if ($errors = $this->validate($request)) {
            return $this->back($errors, $request->all());
        }

        CategoryRepository::update(
            (int) $id,
            $request->string('name'),
            $this->slug($request),
            $request->string('description'),
            $this->uploadImage($request),
            $request->int('position'),
        );
        Session::flash('success', 'Catégorie mise à jour.');

        return $this->redirect('/admin/categories');
    }

    public function delete(Request $request, string $id): Response
    {
        CategoryRepository::delete((int) $id);
        Session::flash('success', 'Catégorie supprimée.');

        return $this->redirect('/admin/categories');
    }

    /** @return array<string,list<string>> */
    private function validate(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|max:120',
            'description' => 'max:2000',
        ]);

        return $validator->fails() ? $validator->errors() : [];
    }

    private function slug(Request $request): string
    {
        return $request->string('slug') !== '' ? slugify($request->string('slug')) : slugify($request->string('name'));
    }

    private function uploadImage(Request $request): ?string
    {
        $file = $request->file('image');

        return $file !== null ? Upload::store($file, 'categories') : null;
    }
}
