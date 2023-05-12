<?php

namespace App\Controller\main;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    #[Route('/books', name: 'book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findAll();

        return $this->render('main/books/index.html.twig', [
            'books' => $books,
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
}