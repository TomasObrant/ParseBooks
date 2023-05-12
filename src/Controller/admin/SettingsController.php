<?php

namespace App\Controller\admin;

use App\Command\ParseBooksCommand;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings_index', methods: ['GET', 'POST'])]
    public function index(ParseBooksCommand $parseBooksCommand): Response
    {
        $sourceUrl = $parseBooksCommand->getBooksSource();

        return $this->render('admin/settings/index.html.twig', [
            'sourceUrl' => $sourceUrl,
        ]);
    }

    #[Route('/admin/settings/parse-url', name: 'admin_settings_parse_url', methods: ['POST'])]
    public function updateParseUrl(Request $request, ParseBooksCommand $parseBooksCommand): Response
    {
        $sourceUrl = $request->request->get('sourceUrl');
        $parseBooksCommand->setBooksSource($sourceUrl);

        return $this->redirectToRoute('admin_settings_index');
    }

    #[Route('/admin/settings/parse-books', name: 'admin_settings_parse_books', methods: ['GET'])]
    public function parseBooks(ManagerRegistry $doctrine)
    {

    }
}