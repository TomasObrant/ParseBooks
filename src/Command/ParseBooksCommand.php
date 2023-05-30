<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'parse:books')]
class ParseBooksCommand extends Command
{

    private const BOOKS_SOURCE = 'https://gitlab.com/prog-positron/test-app-vacancy/-/raw/master/books.json';
    private const IMAGES_DIRECTORY = 'public/images/books';

    private $sourceUrl;

    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    public function setBooksSource($newSourceUrl)
    {
        $this->sourceUrl = $newSourceUrl;
    }

    public function getBooksSource()
    {
        return $this->sourceUrl ?? self::BOOKS_SOURCE;
    }

    protected function configure()
    {
        $this
            ->setName('parse:books')
            ->setDescription('Parse books and categories from the source.')
            ->setHelp('This command will parse books and categories from the given source URL and store them in the database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $booksData = file_get_contents($this->sourceUrl ?? self::BOOKS_SOURCE);

        $books = json_decode($booksData, true);

        $addedBooks = 0;
        $addedCategories = 0;
        $addedAuthors = 0;

        foreach ($books as $book) {

            $em = $this->doctrine->getManager();
            $bookEntity = $em->getRepository(Book::class)->findOneBy([
                'title' => $book['title'],
            ]);

            if (!$bookEntity) {
                $bookEntity = new Book();
                ++$addedBooks;
                $bookEntity->setTitle($book['title']);

                if (!array_key_exists('isbn', $book)) {
                    $bookEntity->setIsbn("unknown");
                } else {
                    $bookEntity->setIsbn($book['isbn']);
                }


                $bookEntity->setPageCount($book['pageCount']);


                if (!array_key_exists('publishedDate', $book)) {
                    $bookEntity->setPublishedDate(new DateTime());
                } else {
                    $bookEntity->setPublishedDate(new DateTime($book['publishedDate']['$date']));
                }


                if (!array_key_exists('shortDescription', $book)) {
                    if (!array_key_exists('longDescription', $book)) {
                        $bookEntity->setShortDescription("none");
                    } else {
                        $shortDesc = explode('.', $book['longDescription'])[0];
                        $bookEntity->setShortDescription($shortDesc);
                    }
                } else {
                    $bookEntity->setShortDescription($book['shortDescription']);
                }


                if (!array_key_exists('longDescription', $book)) {
                    $bookEntity->setLongDescription("none");
                } else {
                    $bookEntity->setLongDescription($book['longDescription']);
                }


                $bookEntity->setStatus($book['status']);





                if (!array_key_exists('thumbnailUrl', $book)) {
                    $bookEntity->setThumbnailUrl("None.jpg");
                } else {
                    $baseName = basename($book['thumbnailUrl']);
                    $imageFilePath = self::IMAGES_DIRECTORY . '/' . $baseName;
                    try {
                        if (!file_exists($imageFilePath)) {
                            $imageContent = file_get_contents($book['thumbnailUrl']);
                            file_put_contents($imageFilePath, $imageContent);
                        }
                        $bookEntity->setThumbnailUrl($baseName);
                    } catch (Exception $e) {
                        echo
                        "Не удалось загрузить изображение - $baseName \n",
                        $e->getMessage(), "\n";
                        $bookEntity->setThumbnailUrl("None.jpg");
                    }
                }



                if (!array_key_exists('categories', $book)) {
                    $defaultCategory = $em->getRepository(Category::class)->findOneBy(['title' => 'New Category']);
                    if (!isset($defaultCategory)) {
                        $defaultCategory = new Category();
                        $defaultCategory->setTitle('New Category');
                        ++$addedCategories;
                    }
                    $em->persist($defaultCategory);
                    $bookEntity->addCategory($defaultCategory);

                } else {
                    foreach ($book['categories'] as $categoryTitle) {
                        if ($categoryTitle === '') $categoryTitle = 'New Category';
                        $category = $em->getRepository(Category::class)->findOneBy(['title' => $categoryTitle]);
                        if (!isset($category)) {
                            $category = new Category();
                            $category->setTitle($categoryTitle);
                            ++$addedCategories;
                        }
                        $em->persist($category);
                        $bookEntity->addCategory($category);

                    }
                }

                if (!array_key_exists('authors', $book)) {
                    $defaultAuthor = $em->getRepository(Author::class)->findOneBy(['name' => 'None']);
                    if (!isset($defaultAuthor)) {
                        $defaultAuthor = new Author();
                        $defaultAuthor->setName('None');
                        ++$addedAuthors;
                    }
                    $em->persist($defaultAuthor);
                    $bookEntity->addAuthor($defaultAuthor);

                } else {
                    foreach ($book['authors'] as $authorName) {
                        if ($authorName === '') $authorName = 'None';
                        $author = $em->getRepository(Author::class)->findOneBy(['name' => $authorName]);
                        if (!isset($author)) {
                            $author = new Author();
                            $author->setName($authorName);
                            ++$addedAuthors;
                        }
                        $em->persist($author);
                        $bookEntity->addAuthor($author);

                    }
                }
                $em->persist($bookEntity);
                $this->doctrine->getManager()->flush();
            }
        }



        $output->writeln(sprintf("Добавлено: \n Книг - %d, \n Категорий - %d, \n Авторов - %d.", $addedBooks, $addedCategories, $addedAuthors));

        return Command::SUCCESS;
    }

}
