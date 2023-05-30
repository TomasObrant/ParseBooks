<?php

namespace App\Controller\admin;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Form\BookType;
use App\Repository\BookRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{

    #[Route('/admin/books', name: 'admin_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $books = $bookRepository->findAll();

        $pageSize = $request->get('limit') ?? 10;

        if ($pageSize == 'all') {
            $pagination = $paginator->paginate(
                $books,
                $request->query->getInt('page', 1),
                count($books)
            );
        } else {
            $pagination = $paginator->paginate(
                $books,
                $request->query->getInt('page', 1),
                $pageSize
            );
        }

        return $this->render('admin/books/index.html.twig', [
            'pagination' => $pagination,
            'pageSize' => $pageSize,
        ]);
    }

    #[Route('admin/books/create', name: 'admin_book_create', methods: ['GET', 'POST'])]
    public function create(ManagerRegistry $doctrine, Request $request, EventDispatcherInterface $dispatcher, EventDispatcherInterface $eventDispatcher): Response
    {
        $book = new Book();

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();

            $imageFile = $form->get('thumbnailUrl')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('book_images_directory'),
                    $newFilename
                );
                $book->setThumbnailUrl($newFilename);
            }

            $authors = json_decode($request->request->get('authors'), true) ?? [];
            foreach ($authors as $authorName) {
                $author = $entityManager->getRepository(Author::class)->findOneBy(['name' => $authorName]);
                if (!isset($author)) {
                    $author = new Author();
                    $author->setName($authorName['value']);
                }
                $entityManager->persist($author);
                $book->addAuthor($author);
            }

            $categories = json_decode($request->request->get('categories'), true) ?? [];
            foreach ($categories as $categoryTitle) {
                $category = $entityManager->getRepository(Category::class)->findOneBy(['title' => $categoryTitle]);
                if (!isset($category)) {
                    $category = new Category();
                    $category->setTitle($categoryTitle['value']);
                }
                $entityManager->persist($category);
                $book->addCategory($category);
            }

            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash('success', 'The book has been created.');

            return $this->redirectToRoute('admin_book_index');
        }

        return $this->render('admin/books/create.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
            'authors' => $doctrine->getRepository(Author::class)->findAll(),
            'categories' => $doctrine->getRepository(Category::class)->findAll(),
        ]);
    }

    #[Route('admin/books/{id}', name: 'admin_book_show', methods: ['GET'])]
    public function show(ManagerRegistry $doctrine, Book $book): Response
    {
        $categories = $book->getCategories();
        $authors = $book->getAuthors();
        return $this->render('admin/books/show.html.twig', [
            'book' => $book,
            'categories' => $categories,
            'authors' => $authors,
        ]);
    }

    #[Route('/books/{id}/update', name: 'admin_book_update', methods: ['GET', 'POST'])]
    public function update(ManagerRegistry $doctrine, Request $request, Book $book): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        $book->loadAuthors();
        $book->loadCategories();

        $bookAuthors = $book->getAuthors()->toArray();
        $bookCategories = $book->getCategories()->toArray();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();

            foreach ($bookAuthors as $author) {
                $author->removeBook($book);
                $book->removeAuthor($author);
            }
            foreach ($bookCategories as $category) {
                $category->removeBook($book);
                $book->removeCategory($category);
            }

            $imageFile = $form->get('thumbnailUrl')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('book_images_directory'),
                    $newFilename
                );
                $book->setThumbnailUrl($newFilename);
            }

            $authors = json_decode($request->request->get('authors'), true) ?? [];
            foreach ($authors as $authorName) {
                $author = $entityManager->getRepository(Author::class)->findOneBy(['name' => $authorName]);
                if (!isset($author)) {
                    $author = new Author();
                    $author->setName($authorName['value']);
                }
                $entityManager->persist($author);
                $book->addAuthor($author);
            }

            $categories = json_decode($request->request->get('categories'), true) ?? [];
            foreach ($categories as $categoryTitle) {
                $category = $entityManager->getRepository(Category::class)->findOneBy(['title' => $categoryTitle]);
                if (!isset($category)) {
                    $category = new Category();
                    $category->setTitle($categoryTitle['value']);
                }
                $entityManager->persist($category);
                $book->addCategory($category);
            }

            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('admin_book_show', ['id' => $book->getId()]);
        }

        return $this->render('admin/books/update.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
            'authors' => $doctrine->getRepository(Author::class)->findAll(),
            'categories' => $doctrine->getRepository(Category::class)->findAll(),
            'bookAuthors' => $bookAuthors,
            'bookCategories' => $bookCategories,
        ]);
    }

    #[Route('admin/books/{id}', name: 'admin_book_delete', methods: ['POST'])]
    public function delete(ManagerRegistry $doctrine, Request $request, Book $book): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_book_index');
    }
}



