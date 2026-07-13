<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Repositories\HomeRepository;
use Cyna\Support\Upload;

/**
 * Personnalisation de la page d'accueil : carrousel (slides) et réglages
 * éditoriaux (texte d'introduction, titre des top produits, pagination).
 *
 * L'ordre des catégories et le choix des « top produits » se règlent
 * respectivement depuis la gestion des catégories et la fiche produit.
 */
final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin/home/index', [
            'title'         => 'Page d\'accueil',
            'slides'        => HomeRepository::allSlides(),
            'introText'     => HomeRepository::setting('home_intro_text'),
            'featuredTitle' => HomeRepository::setting('home_featured_title'),
            'perPage'       => HomeRepository::setting('pagination_per_page', '9'),
        ]);
    }

    public function updateSettings(Request $request): Response
    {
        HomeRepository::setSetting('home_intro_text', $request->string('home_intro_text'));
        HomeRepository::setSetting('home_featured_title', $request->string('home_featured_title'));
        HomeRepository::setSetting('pagination_per_page', (string) max(1, $request->int('pagination_per_page', 9)));

        Session::flash('success', 'Réglages enregistrés.');

        return $this->redirect('/admin/accueil');
    }

    public function storeSlide(Request $request): Response
    {
        HomeRepository::createSlide(
            $request->string('title'),
            $request->string('subtitle') ?: null,
            $this->uploadImage($request),
            $request->string('link_url') ?: null,
            $request->int('position'),
            $request->boolean('is_active'),
        );
        Session::flash('success', 'Slide ajoutée.');

        return $this->redirect('/admin/accueil');
    }

    public function updateSlide(Request $request, string $id): Response
    {
        HomeRepository::updateSlide(
            (int) $id,
            $request->string('title'),
            $request->string('subtitle') ?: null,
            $this->uploadImage($request),
            $request->string('link_url') ?: null,
            $request->int('position'),
            $request->boolean('is_active'),
        );
        Session::flash('success', 'Slide mise à jour.');

        return $this->redirect('/admin/accueil');
    }

    public function deleteSlide(Request $request, string $id): Response
    {
        HomeRepository::deleteSlide((int) $id);
        Session::flash('success', 'Slide supprimée.');

        return $this->redirect('/admin/accueil');
    }

    private function uploadImage(Request $request): ?string
    {
        $file = $request->file('image');

        return $file !== null ? Upload::store($file, 'slides') : null;
    }
}
