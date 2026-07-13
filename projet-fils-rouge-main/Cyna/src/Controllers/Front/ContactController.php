<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Validator;
use Cyna\Repositories\ContactRepository;

/**
 * Formulaire de contact. Les messages sont centralisés dans le back-office
 * pour traitement par l'équipe support.
 */
final class ContactController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('front/contact', ['title' => t('contact.title')]);
    }

    public function submit(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'email'   => 'required|email|max:190',
            'subject' => 'required|max:160',
            'message' => 'required|max:5000',
        ], [
            'email'   => t('contact.email'),
            'subject' => t('contact.subject'),
            'message' => t('contact.message'),
        ]);

        if ($validator->fails()) {
            return $this->back($validator->errors(), $request->all());
        }

        ContactRepository::create(
            $request->string('email'),
            $request->string('subject'),
            $request->string('message'),
        );

        Session::flash('success', t('contact.sent'));

        return $this->redirect('/contact');
    }
}
