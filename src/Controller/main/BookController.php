<?php

namespace App\Controller\main;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Comment;
use App\Repository\BookRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    #[Route('/books', name: 'book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $books = $bookRepository->findAll();

        $pageSize = $request->get('limit') ?? 9;

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

        return $this->render('main/books/index.html.twig', [
            'pagination' => $pagination,
            'pageSize' => $pageSize,
        ]);
    }

    #[Route('/books/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        $categories = $book->getCategories();
        $authors = $book->getAuthors();

        return $this->render('main/books/show.html.twig', [
            'book' => $book,
            'categories' => $categories,
            'authors' => $authors,
        ]);
    }



    #[Route('/books/authors/{id}', name: 'books_by_author', methods: ['GET'])]
    public function booksByAuthor(Author $author): Response
    {
        $books = $author->getBooks();

        return $this->render('main/books/by_author.html.twig', [
            'books' => $books,
            'author' => $author,
        ]);
    }

    #[Route('/books/categories/{id}', name: 'books_by_category', methods: ['GET'])]
    public function booksByCategory(Category $category): Response
    {
        $books = $category->getBooks();

        return $this->render('main/books/by_category.html.twig', [
            'books' => $books,
            'category' => $category,
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(ManagerRegistry $doctrine, Request $request)
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $query = $request->query->get('query');
        $searchBy = $request->query->get('search_by');

        $repository = $doctrine->getRepository(Book::class);
        $queryBuilder = $repository->createQueryBuilder('b');

        switch ($searchBy) {
            case 'title':
                $queryBuilder->andWhere('b.title LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
                break;
            case 'authors':
                $queryBuilder->innerJoin('b.authors', 'a')
                    ->andWhere('a.name LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
                break;
            case 'categories':
                $queryBuilder->innerJoin('b.categories', 'c')
                    ->andWhere('c.title LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
                break;
            case 'text':
                $queryBuilder->andWhere('b.longDescription LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
                break;
        }

        $books = $queryBuilder->getQuery()->getResult();

        return $this->render('main/books/search.html.twig', [
            'books' => $books,
            'form' => $form->createView(),
        ]);
    }
}